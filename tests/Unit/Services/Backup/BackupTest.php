<?php

use App\Events\BackupTaskStatusChanged;
use App\Events\CreatedBackupTaskLog;
use App\Events\StreamBackupTaskLogEvent;
use App\Exceptions\BackupTaskRuntimeException;
use App\Exceptions\BackupTaskZipException;
use App\Exceptions\DatabaseDumpException;
use App\Exceptions\SFTPConnectionException;
use App\Interfaces\SFTPInterface;
use App\Mail\BackupTaskFailed;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use App\Services\Backup\Backup;
use App\Services\Backup\BackupConstants;
use App\Services\Backup\BackupDestinations\S3;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mockSftp = Mockery::mock(SFTPInterface::class);

    $mockSftpFactory = function () {
        return $this->mockSftp;
    };

    $this->backupService = Mockery::mock(Backup::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $this->backupService->shouldReceive('validateConfiguration')->andReturn(null);
    $this->backupService->shouldReceive('logInfo')->byDefault();
    $this->backupService->__construct($mockSftpFactory);
});

afterEach(function () {
    Mockery::close();
});

test('validateConfiguration throws exception if SSH passphrase is not set', function () {
    // Mock the global config function
    mock('config')->shouldReceive('__invoke')
        ->with('app.ssh.passphrase')
        ->andReturn(null);

    mock('config')->shouldReceive('__invoke')
        ->with('app.env')
        ->andReturn('production');

    // Mock the ssh_keys_exist function
    mock('ssh_keys_exist')->shouldReceive('__invoke')
        ->andReturn(true);

    // Mock the Log facade to catch all log calls
    $logMessages = [];
    Log::shouldReceive('info')->andReturnUsing(function ($message) use (&$logMessages) {
        $logMessages[] = ['level' => 'info', 'message' => $message];
    });
    Log::shouldReceive('critical')->andReturnUsing(function ($message) use (&$logMessages) {
        $logMessages[] = ['level' => 'critical', 'message' => $message];
    });

    // Create a concrete implementation of the abstract Backup class
    $backupService = new class extends Backup
    {
        public function __construct() {}  // Empty constructor to avoid calling parent constructor

        public function publicValidateConfiguration()
        {
            $this->validateConfiguration();
        }
    };

    expect(fn () => $backupService->publicValidateConfiguration())
        ->toThrow(BackupTaskRuntimeException::class, 'The SSH passphrase is not set in the configuration.')
        ->and($logMessages)->toContain(['level' => 'info', 'message' => 'Validating configuration.'])
        ->and($logMessages)->toContain(['level' => 'critical', 'message' => 'The SSH passphrase is not set in the configuration.']);
});

test('recordBackupTaskLog creates and dispatches event', function () {
    Event::fake();
    $backupTask = BackupTask::factory()->create();
    $logOutput = 'Sample log output';

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logDebug')->once();

    $backupTaskLog = $this->backupService->recordBackupTaskLog($backupTask->id, $logOutput);

    expect($backupTaskLog->backup_task_id)->toBe($backupTask->id)
        ->and($backupTaskLog->output)->toBe($logOutput);
    Event::assertDispatched(CreatedBackupTaskLog::class);
});

test('updateBackupTaskLogOutput updates log and dispatches event', function () {
    Event::fake();

    $backupTaskLog = BackupTaskLog::factory()->create();
    $logOutput = 'Updated log output';

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logDebug')->twice();

    $this->backupService->updateBackupTaskLogOutput($backupTaskLog, $logOutput);

    $this->assertDatabaseHas('backup_task_logs', [
        'id' => $backupTaskLog->id,
        'output' => $logOutput,
    ]);

    Event::assertDispatched(StreamBackupTaskLogEvent::class);
});

test('updateBackupTaskStatus updates status and dispatches event', function () {
    Event::fake();

    $backupTask = BackupTask::factory()->create();
    $status = 'ready';

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logDebug')->once();

    $this->backupService->updateBackupTaskStatus($backupTask, $status);

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $backupTask->id,
        'status' => $status,
    ]);

    Event::assertDispatched(BackupTaskStatusChanged::class);
});

test('sendEmailNotificationOfTaskFailure sends email notification', function () {
    Mail::fake();

    $backupTask = BackupTask::factory()->create();
    $errorMessage = 'Backup task failed due to some error';

    $this->backupService->shouldReceive('logInfo')->once();

    $this->backupService->sendEmailNotificationOfTaskFailure($backupTask, $errorMessage);

    Mail::assertQueued(BackupTaskFailed::class, function ($mail) use ($backupTask, $errorMessage) {
        return $mail->hasTo($backupTask->user->email) &&
            $mail->taskName === $backupTask->label &&
            $mail->errorMessage === $errorMessage;
    });
});

test('handleFailure logs error, updates logOutput, and sends email notification', function () {
    Mail::fake();

    $backupTask = BackupTask::factory()->create();
    $logOutput = 'Initial log output';
    $errorMessage = 'Backup task failed';

    // Directly call handleFailure
    try {
        $this->backupService->handleFailure($backupTask, $logOutput, $errorMessage);
    } catch (BackupTaskRuntimeException $e) {
        // Check that the exception message is as expected
        expect($e->getMessage())->toBe($errorMessage);
    }

    // Check that the logOutput contains the error message
    expect($logOutput)->toContain($errorMessage);

    // Check that an email was sent
    Mail::assertQueued(BackupTaskFailed::class);
});

test('getRemoteDirectorySize returns correct size', function () {
    $sftp = Mockery::mock(SFTP::class);
    $path = '/remote/path';

    $this->backupService->shouldReceive('logInfo')
        ->once()
        ->with('Getting remote directory size.', ['path' => $path])
        ->ordered();

    $this->backupService->shouldReceive('validateSFTP')
        ->once()
        ->ordered();

    $this->backupService->shouldReceive('logDebug')
        ->once()
        ->with('Directory size command output.', ['output' => '1024'])
        ->ordered();

    $sftp->shouldReceive('exec')->with('du --version')->andReturn('du version');
    $sftp->shouldReceive('exec')->with('du -sb ' . escapeshellarg($path) . ' | cut -f1')->andReturn('1024');
    $sftp->shouldReceive('isConnected')->andReturn(true);

    $size = $this->backupService->getRemoteDirectorySize($sftp, $path);

    expect($size)->toBe(1024);
});

test('checkPathExists returns true if path exists', function () {
    $sftp = Mockery::mock(SFTP::class);
    $path = '/remote/path';

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logDebug')->once();
    $this->backupService->shouldReceive('validateSFTP')->once();

    $sftp->shouldReceive('stat')->with($path)->andReturn(['some' => 'value']);
    $sftp->shouldReceive('isConnected')->andReturn(true);

    $exists = $this->backupService->checkPathExists($sftp, $path);

    expect($exists)->toBeTrue();
});

test('checkPathExists returns false if path does not exist', function () {
    $sftp = Mockery::mock(SFTP::class);
    $path = '/remote/nonexistent-path';

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logDebug')->once();
    $this->backupService->shouldReceive('validateSFTP')->once();

    $sftp->shouldReceive('stat')->with($path)->andReturn(false);
    $sftp->shouldReceive('isConnected')->andReturn(true);

    $exists = $this->backupService->checkPathExists($sftp, $path);

    expect($exists)->toBeFalse();
});

test('establishSFTPConnection creates SFTP connection with default credentials', function () {
    test_create_keys();
    $this->mockSftp->shouldReceive('login')->andReturn(true);
    $this->mockSftp->shouldReceive('getLastError')->andReturn('');

    $mockKey = Mockery::mock(PrivateKey::class);
    $mockPublicKeyLoader = Mockery::mock('alias:' . PublicKeyLoader::class);
    $mockPublicKeyLoader->shouldReceive('load')->andReturn($mockKey);

    $this->backupService->shouldReceive('get_ssh_private_key')->andReturn('mock_private_key');

    $remoteServer = Mockery::mock(RemoteServer::class);
    $remoteServer->shouldReceive('getAttribute')->with('ip_address')->andReturn('192.168.1.1');
    $remoteServer->shouldReceive('getAttribute')->with('port')->andReturn(22);
    $remoteServer->shouldReceive('getAttribute')->with('username')->andReturn('user');
    $remoteServer->shouldReceive('markAsOnlineIfStatusIsNotOnline')->once();

    $backupTask = Mockery::mock(BackupTask::class);
    $backupTask->shouldReceive('hasIsolatedCredentials')->andReturn(false);

    Config::set('app.ssh.passphrase', 'passphrase');

    $this->backupService->shouldReceive('createSFTP')->andReturn($this->mockSftp);

    $result = $this->backupService->establishSFTPConnection($remoteServer, $backupTask);

    expect($result)->toBe($this->mockSftp);
    test_restore_keys();
});

test('establishSFTPConnection creates SFTP connection with isolated credentials', function () {
    test_create_keys();
    // Mock SFTP class
    $this->mockSftp->shouldReceive('__construct')->andReturnSelf();
    $this->mockSftp->shouldReceive('login')->andReturn(true);
    $this->mockSftp->shouldReceive('getLastError')->andReturn('');

    // Mock PublicKeyLoader
    $mockKey = Mockery::mock(PrivateKey::class);
    $mockPublicKeyLoader = Mockery::mock('alias:' . PublicKeyLoader::class);
    $mockPublicKeyLoader->shouldReceive('load')->andReturn($mockKey);

    // Mock SSH key helper function
    $this->backupService->shouldReceive('get_ssh_private_key')->andReturn('mock_private_key');

    $remoteServer = Mockery::mock(RemoteServer::class);
    $remoteServer->shouldReceive('getAttribute')->with('ip_address')->andReturn('192.168.1.1');
    $remoteServer->shouldReceive('getAttribute')->with('port')->andReturn(22);
    $remoteServer->shouldReceive('markAsOnlineIfStatusIsNotOnline')->once();

    $backupTask = Mockery::mock(BackupTask::class);
    $backupTask->shouldReceive('hasIsolatedCredentials')->andReturn(true);
    $backupTask->shouldReceive('getAttribute')->with('isolated_username')->andReturn('isolated_user');
    $backupTask->shouldReceive('getAttribute')->with('isolated_password')->andReturn('encrypted_password');

    Crypt::shouldReceive('decryptString')
        ->with('encrypted_password')
        ->andReturn('decrypted_password');

    Config::set('app.ssh.passphrase', 'passphrase');

    $this->backupService->shouldReceive('logInfo')->twice();
    $this->backupService->shouldReceive('createSFTP')->andReturn($this->mockSftp);

    $result = $this->backupService->establishSFTPConnection($remoteServer, $backupTask);

    expect($result)->toBeInstanceOf(SFTPInterface::class);
    test_restore_keys();
});

test('zipRemoteDirectory zips directory successfully', function () {
    $sftp = Mockery::mock(SFTP::class);
    $sourcePath = '/remote/source';
    $remoteZipPath = '/remote/destination.zip';
    $excludeDirs = ['node_modules', 'vendor'];

    $executedCommands = [];

    $sftp->shouldReceive('exec')->andReturnUsing(function ($command) use (&$executedCommands) {
        $executedCommands[] = $command;

        if (str_contains($command, 'du -sb')) {
            return '1000000';
        }
        if (str_contains($command, 'df -P')) {
            return '2000000';
        }
        if (str_contains($command, 'zip -rv')) {
            return 'adding: file1 (deflated 60%)';
        }
        if (str_contains($command, 'test -f')) {
            return '500000';
        }

        return '';
    });

    $sftp->shouldReceive('isConnected')->andReturn(true);

    $this->backupService->shouldReceive('logInfo')->times(4);
    $this->backupService->shouldReceive('logDebug')->times(2);
    $this->backupService->shouldReceive('validateSFTP')->once();
    $this->backupService->shouldReceive('retryCommand')->once()->andReturn(true);
    $this->backupService->zipRemoteDirectory($sftp, $sourcePath, $remoteZipPath, $excludeDirs);
    expect(true)->toBeTrue();
});

test('zipRemoteDirectory throws exception when not enough disk space', function () {
    $sftp = Mockery::mock(SFTP::class);
    $sourcePath = '/remote/source';
    $remoteZipPath = '/remote/destination.zip';

    $sftp->shouldReceive('exec')->with('du -sb ' . escapeshellarg($sourcePath) . ' | cut -f1')->andReturn('2000000');
    $sftp->shouldReceive('exec')->with('df -P ' . escapeshellarg(dirname($remoteZipPath)) . ' | tail -1 | awk \'{print $4}\'')->andReturn('1000');
    $sftp->shouldReceive('isConnected')->andReturn(true);

    $this->backupService->shouldReceive('logInfo')->times(3);
    $this->backupService->shouldReceive('logError')->once();
    $this->backupService->shouldReceive('validateSFTP')->once();

    $this->expectException(BackupTaskZipException::class);
    $this->expectExceptionMessage('Not enough disk space to create the zip file.');

    $this->backupService->zipRemoteDirectory($sftp, $sourcePath, $remoteZipPath);
});

test('getDatabaseType detects MySQL correctly', function () {
    $sftp = Mockery::mock(SFTP::class);
    $sftp->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');
    $sftp->shouldReceive('exec')->with('psql --version 2>&1')->andReturn('command not found');
    $sftp->shouldReceive('isConnected')->andReturn(true);

    $this->backupService->shouldReceive('logInfo')->twice();
    $this->backupService->shouldReceive('validateSFTP')->once();

    $result = $this->backupService->getDatabaseType($sftp);

    expect($result)->toBe(BackupConstants::DATABASE_TYPE_MYSQL);
});

test('getDatabaseType detects PostgreSQL correctly', function () {
    $sftp = Mockery::mock(SFTP::class);
    $sftp->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('command not found');
    $sftp->shouldReceive('exec')->with('psql --version 2>&1')->andReturn('psql (PostgreSQL) 13.4');
    $sftp->shouldReceive('isConnected')->andReturn(true);

    $this->backupService->shouldReceive('logInfo')->twice();
    $this->backupService->shouldReceive('validateSFTP')->once();

    $result = $this->backupService->getDatabaseType($sftp);

    expect($result)->toBe(BackupConstants::DATABASE_TYPE_POSTGRESQL);
});

test('getDatabaseType throws exception when no supported database is found', function () {
    $sftp = Mockery::mock(SFTP::class);
    $sftp->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('command not found');
    $sftp->shouldReceive('exec')->with('psql --version 2>&1')->andReturn('command not found');
    $sftp->shouldReceive('isConnected')->andReturn(true);

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logError')->once();
    $this->backupService->shouldReceive('validateSFTP')->once();

    $this->expectException(DatabaseDumpException::class);
    $this->expectExceptionMessage('No supported database found on the remote server.');

    $this->backupService->getDatabaseType($sftp);
});

test('dumpRemoteDatabase dumps MySQL database correctly', function () {
    $sftp = Mockery::mock(SFTP::class);
    $databaseType = BackupConstants::DATABASE_TYPE_MYSQL;
    $remoteDumpPath = '/tmp/dump.sql';
    $databasePassword = 'password';
    $databaseName = 'testdb';
    $databaseTablesToExcludeInTheBackup = 'table1,table2';

    // Mock the database dump command execution
    $sftp->shouldReceive('exec')
        ->with(Mockery::pattern('/^mysqldump/'))
        ->once()
        ->andReturn('');

    // Mock the file check command
    $sftp->shouldReceive('exec')
        ->with(Mockery::pattern('/^test -s/'))
        ->once()
        ->andReturn('exists');

    // Mock the file content check
    $sftp->shouldReceive('exec')
        ->with(Mockery::pattern('/^cat/'))
        ->once()
        ->andReturn('Some database content');

    $sftp->shouldReceive('isConnected')->andReturn(true);

    $this->backupService->shouldReceive('logInfo')->twice();
    $this->backupService->shouldReceive('logDebug')->times(3);
    $this->backupService->shouldReceive('validateSFTP')->once();

    $this->backupService->dumpRemoteDatabase($sftp, $databaseType, $remoteDumpPath, $databasePassword, $databaseName, $databaseTablesToExcludeInTheBackup);

    // If no exception is thrown, the test passes
    expect(true)->toBeTrue();
});

test('dumpRemoteDatabase throws exception when dump fails', function () {
    $sftp = Mockery::mock(SFTP::class);
    $databaseType = BackupConstants::DATABASE_TYPE_MYSQL;
    $remoteDumpPath = '/tmp/dump.sql';
    $databasePassword = 'password';
    $databaseName = 'testdb';

    // Mock the database dump command execution with an error
    $sftp->shouldReceive('exec')
        ->with(Mockery::pattern('/^mysqldump/'))
        ->once()
        ->andReturn('Error: Access denied');

    $sftp->shouldReceive('isConnected')->andReturn(true);

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logDebug')->twice();
    $this->backupService->shouldReceive('logError')->once();
    $this->backupService->shouldReceive('validateSFTP')->once();

    $this->expectException(DatabaseDumpException::class);
    $this->expectExceptionMessage('Failed to dump the database: Error: Access denied');

    $this->backupService->dumpRemoteDatabase($sftp, $databaseType, $remoteDumpPath, $databasePassword, $databaseName, null);
});

test('validateSFTP throws exception when connection is lost', function () {
    $sftp = Mockery::mock(SFTP::class);
    $sftp->shouldReceive('isConnected')->andReturn(false);

    $this->backupService->shouldReceive('logError')->once();

    $this->expectException(SFTPConnectionException::class);
    $this->expectExceptionMessage('SFTP connection lost.');

    $this->backupService->validateSFTP($sftp);
});

test('retryCommand retries specified number of times', function () {
    $attempts = 0;
    $maxAttempts = 3;

    $command = function () use (&$attempts, $maxAttempts) {
        $attempts++;

        return $attempts === $maxAttempts;
    };

    $this->backupService->shouldReceive('logWarning')->times($maxAttempts - 1);

    $result = $this->backupService->retryCommand($command, $maxAttempts, 0);

    expect($result)->toBeTrue()
        ->and($attempts)->toBe($maxAttempts);
});

test('retryCommand returns false when max attempts reached', function () {
    $attempts = 0;
    $maxAttempts = 3;

    $command = function () use (&$attempts) {
        $attempts++;

        return false;
    };

    $this->backupService->shouldReceive('logWarning')->times($maxAttempts);

    $result = $this->backupService->retryCommand($command, $maxAttempts, 0);

    expect($result)->toBeFalse()
        ->and($attempts)->toBe($maxAttempts);
});

test('isLaravelDirectory returns true for Laravel directory', function () {
    $sftp = Mockery::mock(SFTP::class);
    $sourcePath = '/remote/laravel';

    $sftp->shouldReceive('stat')->with("{$sourcePath}/artisan")->andReturn(['some' => 'data']);
    $sftp->shouldReceive('stat')->with("{$sourcePath}/composer.json")->andReturn(['some' => 'data']);
    $sftp->shouldReceive('stat')->with("{$sourcePath}/package.json")->andReturn(['some' => 'data']);

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logDebug')->once();

    $result = $this->backupService->isLaravelDirectory($sftp, $sourcePath);

    expect($result)->toBeTrue();
});

test('isLaravelDirectory returns false for non-Laravel directory', function () {
    $sftp = Mockery::mock(SFTP::class);
    $sourcePath = '/remote/not-laravel';

    $sftp->shouldReceive('stat')->with("{$sourcePath}/artisan")->andReturn(false);
    $sftp->shouldReceive('stat')->with("{$sourcePath}/composer.json")->andReturn(['some' => 'data']);
    $sftp->shouldReceive('stat')->with("{$sourcePath}/package.json")->andReturn(['some' => 'data']);

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logDebug')->once();

    $result = $this->backupService->isLaravelDirectory($sftp, $sourcePath);

    expect($result)->toBeFalse();
});

test('deleteFolder deletes folder successfully', function () {
    $sftp = Mockery::mock(SFTP::class);
    $folderPath = '/remote/folder-to-delete';

    $sftp->shouldReceive('exec')->with('rm -rf ' . escapeshellarg($folderPath))->andReturn('');

    $this->backupService->shouldReceive('logInfo')->twice();

    $this->backupService->deleteFolder($sftp, $folderPath);

    // If no exception is thrown and logs are called as expected, the test passes
    expect(true)->toBeTrue();
});

test('deleteFolder logs error when deletion fails', function () {
    $sftp = Mockery::mock(SFTP::class);
    $folderPath = '/remote/folder-to-delete';

    $sftp->shouldReceive('exec')->with('rm -rf ' . escapeshellarg($folderPath))->andReturn(false);
    $sftp->shouldReceive('getLastError')->andReturn('Permission denied');

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logError')->once();

    $this->backupService->deleteFolder($sftp, $folderPath);

    // If logs are called as expected, the test passes
    expect(true)->toBeTrue();
});

test('createBackupDestinationInstance creates S3 instance', function () {
    $backupDestinationModel = Mockery::mock(BackupDestination::class);
    $backupDestinationModel->shouldReceive('getAttribute')
        ->with('type')
        ->andReturn(BackupConstants::DRIVER_S3);
    $backupDestinationModel->shouldReceive('getAttribute')
        ->with('s3_bucket_name')
        ->andReturn('test-bucket');

    $s3Client = Mockery::mock('Aws\S3\S3Client');
    $backupDestinationModel->shouldReceive('getS3Client')->andReturn($s3Client);

    $result = $this->backupService->createBackupDestinationInstance($backupDestinationModel);

    expect($result)->toBeInstanceOf(S3::class);
});

test('createBackupDestinationInstance throws exception for unsupported type', function () {
    $backupDestinationModel = Mockery::mock(BackupDestination::class);
    $backupDestinationModel->shouldReceive('getAttribute')
        ->with('type')
        ->andReturn('unsupported_type');

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Unsupported backup destination type: unsupported_type');

    $this->backupService->createBackupDestinationInstance($backupDestinationModel);
});

function mockPublicKeyLoader($mockPrivateKey): void
{
    phpseclib3\Crypt\PublicKeyLoader::loadClass();
    $mock = Mockery::mock('alias:phpseclib3\Crypt\PublicKeyLoader');
    $mock->shouldReceive('load')
        ->with('private_key', 'passphrase')
        ->andReturn($mockPrivateKey);
}
