<?php

declare(strict_types=1);
use App\Exceptions\BackupTaskRuntimeException;
use App\Exceptions\BackupTaskZipException;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Destinations\Contracts\BackupDestinationInterface;
use App\Services\Backup\Destinations\S3;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Config;
use Tests\Unit\Services\Backup\BackupTestClass;

beforeEach(function (): void {
    Event::fake();
    $this->backup = Mockery::mock(BackupTestClass::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $this->mockSftp = Mockery::mock(SFTPInterface::class);
    $this->backup->shouldReceive('get_ssh_private_key')->andReturn('mock_private_key_content');
});

afterEach(function (): void {
    Mockery::close();
});

it('validates configuration successfully', function (): void {
    Config::set('app.ssh.passphrase', 'test_passphrase');
    Config::set('app.env', 'testing');

    $this->backup->shouldReceive('ssh_keys_exist')->andReturn(true);

    expect(fn () => $this->backup->publicValidateConfiguration())->not->toThrow(BackupTaskRuntimeException::class);
});

it('throws exception when SSH passphrase is not set', function (): void {
    Config::set('app.ssh.passphrase', null);
    Config::set('app.env', 'production');

    $this->backup->shouldReceive('ssh_keys_exist')->andReturn(true);

    expect(fn () => $this->backup->publicValidateConfiguration())->toThrow(BackupTaskRuntimeException::class);
});

it('obtains backup task', function (): void {
    $backupTask = BackupTask::factory()->create();

    $obtainedTask = $this->backup->obtainBackupTask($backupTask->id);

    expect($obtainedTask->id)->toBe($backupTask->id);
});

it('records backup task log', function (): void {
    $backupTask = BackupTask::factory()->create();
    $logOutput = 'Test log output';

    $backupTaskLog = $this->backup->recordBackupTaskLog($backupTask->id, $logOutput);

    expect($backupTaskLog)->toBeInstanceOf(BackupTaskLog::class)
        ->and($backupTaskLog->backup_task_id)->toBe($backupTask->id)
        ->and($backupTaskLog->output)->toBe($logOutput);
});

it('updates backup task log output', function (): void {
    $backupTaskLog = BackupTaskLog::factory()->create();
    $newLogOutput = 'Updated log output';

    $this->backup->updateBackupTaskLogOutput($backupTaskLog, $newLogOutput);

    expect($backupTaskLog->fresh()->output)->toBe($newLogOutput);
});

it('updates backup task status', function (): void {
    $backupTask = BackupTask::factory()->create(['status' => 'ready']);
    $newStatus = 'ready';

    $this->backup->updateBackupTaskStatus($backupTask, $newStatus);

    $backupTask->refresh();
    expect($backupTask->status)->toBe($newStatus);
});

it('checks if path exists', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source')->andReturn(['type' => 2]); // 2 for directory

    $result = $this->backup->checkPathExists($this->mockSftp, '/path/to/source');

    expect($result)->toBeTrue();
});

it('gets remote directory size', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with("du -sb '/path/to/source' | cut -f1")->andReturn('1024');

    $result = $this->backup->getRemoteDirectorySize($this->mockSftp, '/path/to/source');

    expect($result)->toBe(1024);
});

it('establishes SFTP connection', function (): void {
    test_create_keys();
    $remoteServer = RemoteServer::factory()->create([
        'ip_address' => '192.168.1.1',
        'port' => 22,
        'username' => 'testuser',
        'connectivity_status' => 'offline',
    ]);

    $backupTask = BackupTask::factory()->create([
        'remote_server_id' => $remoteServer->id,
    ]);

    $mockSftp = Mockery::mock(SFTPInterface::class);
    $mockSftp->shouldReceive('login')->andReturn(true);

    $this->backup->shouldReceive('createSFTP')->andReturn($mockSftp);
    $this->backup->shouldReceive('get_ssh_private_key')->andReturn('mock_private_key');

    Mockery::mock('alias:phpseclib3\Crypt\PublicKeyLoader')
        ->shouldReceive('load')
        ->andReturn(Mockery::mock('phpseclib3\Crypt\Common\PrivateKey'));

    $sftp = $this->backup->establishSFTPConnection($backupTask);

    expect($sftp)->toBe($mockSftp)
        ->and($remoteServer->fresh()->connectivity_status)->toBe('online');
    test_restore_keys();
});

it('zips remote directory successfully', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $this->mockSftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');

    // Mock Laravel directory check (not a Laravel directory)
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/artisan')->andReturn(false);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/composer.json')->andReturn(false);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/package.json')->andReturn(false);

    // New approach uses a log file and filters zip output
    $this->mockSftp->shouldReceive('exec')
        ->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rX '\/tmp\/backup\.zip' \./"))
        ->andReturn('');

    // Test file existence and size check
    $this->mockSftp->shouldReceive('exec')
        ->with("test -f '/tmp/backup.zip' && stat -c%s '/tmp/backup.zip'")
        ->andReturn('512');

    // Cat the log file to check for errors
    $this->mockSftp->shouldReceive('exec')
        ->with("cat '/tmp/backup.zip.log' 2>/dev/null || echo \"\"")
        ->andReturn('');

    // Remove log file
    $this->mockSftp->shouldReceive('exec')
        ->with("rm -f '/tmp/backup.zip.log' 2>/dev/null")
        ->andReturn('');

    $this->backup->zipRemoteDirectory($this->mockSftp, '/path/to/source', '/tmp/backup.zip', []);

    expect(true)->toBeTrue();
});

it('throws exception when zipping fails due to zero file size', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $this->mockSftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');

    // Mock Laravel directory check (not a Laravel directory)
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/artisan')->andReturn(false);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/composer.json')->andReturn(false);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/package.json')->andReturn(false);

    // Mock the zip command execution
    $this->mockSftp->shouldReceive('exec')
        ->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rX '\/tmp\/backup\.zip' \./"))
        ->andReturn('');

    // Mock the log file contents check - no errors in log
    $this->mockSftp->shouldReceive('exec')
        ->with("cat '/tmp/backup.zip.log' 2>/dev/null || echo \"\"")
        ->andReturn('');

    // Mock the log file removal
    $this->mockSftp->shouldReceive('exec')
        ->with("rm -f '/tmp/backup.zip.log' 2>/dev/null")
        ->andReturn('');

    // Mock file check - file doesn't exist or is empty
    $this->mockSftp->shouldReceive('exec')
        ->with("test -f '/tmp/backup.zip' && stat -c%s '/tmp/backup.zip'")
        ->andReturn('');  // This should trigger the exception (non-numeric value)

    expect(fn () => $this->backup->zipRemoteDirectory($this->mockSftp, '/path/to/source', '/tmp/backup.zip', []))
        ->toThrow(BackupTaskZipException::class);
});

it('throws exception when zipping reports an error in log file', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $this->mockSftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');

    // Mock Laravel directory check (not a Laravel directory)
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/artisan')->andReturn(false);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/composer.json')->andReturn(false);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/package.json')->andReturn(false);

    // Mock the zip command execution
    $this->mockSftp->shouldReceive('exec')
        ->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rX '\/tmp\/backup\.zip' \./"))
        ->andReturn('');

    // Mock the log file containing errors
    $this->mockSftp->shouldReceive('exec')
        ->with("cat '/tmp/backup.zip.log' 2>/dev/null || echo \"\"")
        ->andReturn('error: could not create zip file');

    // Mock the log file removal
    $this->mockSftp->shouldReceive('exec')
        ->with("rm -f '/tmp/backup.zip.log' 2>/dev/null")
        ->andReturn('');

    // The command to retry with retryCommand should be mocked to ensure it works
    $this->backup->shouldReceive('retryCommand')
        ->once()
        ->andReturn('error: could not create zip file');

    expect(fn () => $this->backup->zipRemoteDirectory($this->mockSftp, '/path/to/source', '/tmp/backup.zip', []))
        ->toThrow(BackupTaskZipException::class, 'Failed to zip the directory after multiple attempts: error: could not create zip file');
});

it('throws exception when not enough disk space for zip', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('5000000'); // 5MB source
    $this->mockSftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('1000'); // Only 1MB available

    // Mock Laravel directory check (not a Laravel directory)
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/artisan')->andReturn(false);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/composer.json')->andReturn(false);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/package.json')->andReturn(false);

    expect(fn () => $this->backup->zipRemoteDirectory($this->mockSftp, '/path/to/source', '/tmp/backup.zip', []))
        ->toThrow(BackupTaskZipException::class, 'Not enough disk space to create the zip file.');
});

it('detects Laravel directory and applies standard exclusions', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $this->mockSftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');

    // Make it detect as Laravel directory
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/artisan')->andReturn(['type' => 1]);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/composer.json')->andReturn(['type' => 1]);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source/package.json')->andReturn(['type' => 1]);

    // Check that it uses exclusions in the command
    $this->mockSftp->shouldReceive('exec')
        ->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rX '\/tmp\/backup\.zip' \. --exclude='node_modules\/\*'/"))
        ->andReturn('');

    // Cat the log file to check for errors
    $this->mockSftp->shouldReceive('exec')
        ->with("cat '/tmp/backup.zip.log' 2>/dev/null || echo \"\"")
        ->andReturn('');

    // Remove log file
    $this->mockSftp->shouldReceive('exec')
        ->with("rm -f '/tmp/backup.zip.log' 2>/dev/null")
        ->andReturn('');

    // Verify file exists and has non-zero size
    $this->mockSftp->shouldReceive('exec')
        ->with("test -f '/tmp/backup.zip' && stat -c%s '/tmp/backup.zip'")
        ->andReturn('512');

    $this->backup->zipRemoteDirectory($this->mockSftp, '/path/to/source', '/tmp/backup.zip', []);

    expect(true)->toBeTrue();
});

it('gets database type', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');

    $dbType = $this->backup->getDatabaseType($this->mockSftp);

    expect($dbType)->toBe('mysql');
});

it('dumps remote database', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^mysqldump/'))->andReturn('');
    $this->mockSftp->shouldReceive('exec')->with("cat '/path/to/dump.sql.error.log'")->andReturn('');
    $this->mockSftp->shouldReceive('exec')->with("rm '/path/to/dump.sql.error.log'")->andReturn('');
    $this->mockSftp->shouldReceive('exec')
        ->with(Mockery::pattern("/stat -c %s '\/path\/to\/dump\.sql' || echo \"0\"/"))
        ->andReturn('1024');

    $this->backup->dumpRemoteDatabase(
        $this->mockSftp,
        'mysql',
        '/path/to/dump.sql',
        'password',
        'testdb',
        null
    );

    expect(true)->toBeTrue();
});

it('checks if directory is a Laravel project', function (): void {
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/artisan')->andReturn(['type' => 1]);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/composer.json')->andReturn(['type' => 1]);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/package.json')->andReturn(['type' => 1]);

    $isLaravel = $this->backup->isLaravelDirectory($this->mockSftp, '/path/to/laravel');

    expect($isLaravel)->toBeTrue();
});

it('deletes folder', function (): void {
    $this->mockSftp->shouldReceive('exec')->with("rm -rf '/path/to/delete'")->andReturn('');

    $this->backup->deleteFolder($this->mockSftp, '/path/to/delete');

    expect(true)->toBeTrue();
});

it('creates backup destination instance', function (): void {
    $mock = Mockery::mock(BackupDestination::class);
    $mock->shouldReceive('getAttribute')->with('type')->andReturn('s3');
    $mock->shouldReceive('getAttribute')->with('s3_bucket_name')->andReturn('test-bucket');
    $mock->shouldReceive('getS3Client')->andReturn(Mockery::mock(S3Client::class));

    $s3Mock = Mockery::mock(S3::class, [Mockery::mock(S3Client::class), 'test-bucket']);
    $s3Mock->shouldReceive('listFiles')->andReturn([]);
    $s3Mock->shouldReceive('deleteFile')->andReturn(null);
    $s3Mock->shouldReceive('streamFiles')->andReturn(true);

    $instance = $this->backup->createBackupDestinationInstance($mock);

    expect($instance)->toBeInstanceOf(BackupDestinationInterface::class);
});

it('gets excluded directories for Laravel project', function (): void {
    // Simulate Laravel project detection
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/artisan')->andReturn(['type' => 1]);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/composer.json')->andReturn(['type' => 1]);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/package.json')->andReturn(['type' => 1]);

    // Mock finding symlinks
    $this->mockSftp->shouldReceive('exec')
        ->with(Mockery::pattern("/find '\/path\/to\/laravel' -type l -printf/"))
        ->andReturn("public/storage\nsome/other/link");

    $excludedDirs = $this->backup->getExcludedDirectories($this->mockSftp, '/path/to/laravel');

    // Should contain both Laravel standard exclusions and the symlinks
    expect($excludedDirs)->toContain('node_modules/*')
        ->toContain('vendor/*')
        ->toContain('public/storage')
        ->toContain('some/other/link');
});
