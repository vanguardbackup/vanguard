<?php

use App\Exceptions\BackupTaskRuntimeException;
use App\Exceptions\BackupTaskZipException;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Destinations\Contracts\BackupDestinationInterface;
use App\Services\Backup\Destinations\S3;
use Illuminate\Support\Facades\Config;
use Tests\Unit\Services\Backup\BackupTestClass;

beforeEach(function () {
    $this->backup = Mockery::mock(BackupTestClass::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $this->mockSftp = Mockery::mock(SFTPInterface::class);
    $this->backup->shouldReceive('get_ssh_private_key')->andReturn('mock_private_key_content');
});

afterEach(function () {
    Mockery::close();
});

it('validates configuration successfully', function () {
    Config::set('app.ssh.passphrase', 'test_passphrase');
    Config::set('app.env', 'testing');

    $this->backup->shouldReceive('ssh_keys_exist')->andReturn(true);

    expect(fn () => $this->backup->publicValidateConfiguration())->not->toThrow(BackupTaskRuntimeException::class);
});

it('throws exception when SSH passphrase is not set', function () {
    Config::set('app.ssh.passphrase', null);
    Config::set('app.env', 'production');

    $this->backup->shouldReceive('ssh_keys_exist')->andReturn(true);

    expect(fn () => $this->backup->publicValidateConfiguration())->toThrow(BackupTaskRuntimeException::class);
});

it('obtains backup task', function () {
    $backupTask = BackupTask::factory()->create();

    $obtainedTask = $this->backup->obtainBackupTask($backupTask->id);

    expect($obtainedTask->id)->toBe($backupTask->id);
});

it('records backup task log', function () {
    $backupTask = BackupTask::factory()->create();
    $logOutput = 'Test log output';

    $backupTaskLog = $this->backup->recordBackupTaskLog($backupTask->id, $logOutput);

    expect($backupTaskLog)->toBeInstanceOf(BackupTaskLog::class)
        ->and($backupTaskLog->backup_task_id)->toBe($backupTask->id)
        ->and($backupTaskLog->output)->toBe($logOutput);
});

it('updates backup task log output', function () {
    $backupTaskLog = BackupTaskLog::factory()->create();
    $newLogOutput = 'Updated log output';

    $this->backup->updateBackupTaskLogOutput($backupTaskLog, $newLogOutput);

    expect($backupTaskLog->fresh()->output)->toBe($newLogOutput);
});

it('updates backup task status', function () {
    $backupTask = BackupTask::factory()->create(['status' => 'ready']);
    $newStatus = 'ready';

    $this->backup->updateBackupTaskStatus($backupTask, $newStatus);

    $backupTask->refresh();
    expect($backupTask->status)->toBe($newStatus);
});

it('checks if path exists', function () {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source')->andReturn(['type' => 2]); // 2 for directory

    $result = $this->backup->checkPathExists($this->mockSftp, '/path/to/source');

    expect($result)->toBeTrue();
});

it('gets remote directory size', function () {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with("du -sb '/path/to/source' | cut -f1")->andReturn('1024');

    $result = $this->backup->getRemoteDirectorySize($this->mockSftp, '/path/to/source');

    expect($result)->toBe(1024);
});

it('establishes SFTP connection', function () {
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

    $sftp = $this->backup->establishSFTPConnection($remoteServer, $backupTask);

    expect($sftp)->toBe($mockSftp)
        ->and($remoteServer->fresh()->connectivity_status)->toBe('online');
    test_restore_keys();
});

it('zips remote directory successfully', function () {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $this->mockSftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rv '\/tmp\/backup\.zip' \./"))
        ->andReturn('adding: somefile (stored 0%)');
    $this->mockSftp->shouldReceive('exec')->with("test -f '/tmp/backup.zip' && stat -c%s '/tmp/backup.zip'")->andReturn('512');

    $this->backup->zipRemoteDirectory($this->mockSftp, '/path/to/source', '/tmp/backup.zip', []);

    expect(true)->toBeTrue();
});

it('throws exception when zipping fails', function () {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $this->mockSftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rv '\/tmp\/backup\.zip' \./"))
        ->andReturn('zip error: Command failed');
    $this->mockSftp->shouldReceive('exec')->with("test -f '/tmp/backup.zip' && stat -c%s '/tmp/backup.zip'")->andReturn('0');

    expect(fn () => $this->backup->zipRemoteDirectory($this->mockSftp, '/path/to/source', '/tmp/backup.zip', []))
        ->toThrow(BackupTaskZipException::class);
});

it('gets database type', function () {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');

    $dbType = $this->backup->getDatabaseType($this->mockSftp);

    expect($dbType)->toBe('mysql');
});

it('dumps remote database', function () {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^mysqldump/'))->andReturn('');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^test -s/'))->andReturn('exists');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^cat/'))->andReturn('dump content');

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

it('checks if directory is a Laravel project', function () {
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/artisan')->andReturn(['type' => 1]);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/composer.json')->andReturn(['type' => 1]);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/package.json')->andReturn(['type' => 1]);

    $isLaravel = $this->backup->isLaravelDirectory($this->mockSftp, '/path/to/laravel');

    expect($isLaravel)->toBeTrue();
});

it('deletes folder', function () {
    $this->mockSftp->shouldReceive('exec')->with("rm -rf '/path/to/delete'")->andReturn('');

    $this->backup->deleteFolder($this->mockSftp, '/path/to/delete');

    expect(true)->toBeTrue();
});

it('creates backup destination instance', function () {
    $backupDestination = Mockery::mock(BackupDestination::class);
    $backupDestination->shouldReceive('getAttribute')->with('type')->andReturn('s3');
    $backupDestination->shouldReceive('getAttribute')->with('s3_bucket_name')->andReturn('test-bucket');
    $backupDestination->shouldReceive('getS3Client')->andReturn(Mockery::mock('Aws\S3\S3Client'));

    $s3Mock = Mockery::mock(S3::class, [Mockery::mock('Aws\S3\S3Client'), 'test-bucket']);
    $s3Mock->shouldReceive('listFiles')->andReturn([]);
    $s3Mock->shouldReceive('deleteFile')->andReturn(null);
    $s3Mock->shouldReceive('streamFiles')->andReturn(true);

    $instance = $this->backup->createBackupDestinationInstance($backupDestination);

    expect($instance)->toBeInstanceOf(BackupDestinationInterface::class);
});
