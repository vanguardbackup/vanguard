<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Backup\Tasks;

use App\Exceptions\BackupTaskZipException;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use App\Services\Backup\Adapters\SFTPAdapter;
use App\Services\Backup\BackupConstants;
use App\Services\Backup\Destinations\S3;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use RuntimeException;
use Tests\Unit\Services\Backup\Tasks\Helpers\FileBackupTaskTestClass;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Event::fake();
    $this->remoteServer = RemoteServer::factory()->create();
    $this->backupDestination = BackupDestination::factory()->create([
        'type' => BackupConstants::DRIVER_S3,
    ]);
    $this->backupTask = BackupTask::factory()->create([
        'remote_server_id' => $this->remoteServer->id,
        'backup_destination_id' => $this->backupDestination->id,
        'source_path' => '/path/to/backup',
        'store_path' => '/backups',
    ]);

    $this->sftpMock = Mockery::mock(SFTPAdapter::class);
    $this->sftpMock->shouldReceive('isConnected')->andReturn(true);
    $this->sftpMock->shouldReceive('exec')->andReturn(''); // Default behavior
    $this->sftpMock->shouldReceive('delete')->andReturn(true)->byDefault();

    // Add default mocks for Laravel directory check
    $this->sftpMock->shouldReceive('stat')->with('/path/to/backup/artisan')->andReturn(false)->byDefault();
    $this->sftpMock->shouldReceive('stat')->with('/path/to/backup/composer.json')->andReturn(false)->byDefault();
    $this->sftpMock->shouldReceive('stat')->with('/path/to/backup/package.json')->andReturn(false)->byDefault();

    // Add mocks for the new zip process
    $this->sftpMock->shouldReceive('exec')
        ->with(Mockery::pattern("/^cat.*\.log.*|| echo \"\"/"))
        ->andReturn('')
        ->byDefault();
    $this->sftpMock->shouldReceive('exec')
        ->with(Mockery::pattern("/^rm -f.*\.log.*/"))
        ->andReturn('')
        ->byDefault();
    $this->sftpMock->shouldReceive('exec')
        ->with(Mockery::pattern("/^test -f.*&& stat -c%s.*/"))
        ->andReturn('1024')
        ->byDefault();

    $this->s3Mock = Mockery::mock(S3::class);

    $this->fileBackupTask = Mockery::mock(FileBackupTaskTestClass::class, [$this->backupTask->id])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $this->fileBackupTask->shouldReceive('establishSFTPConnection')->andReturn($this->sftpMock);
    $this->fileBackupTask->shouldReceive('recordBackupTaskLog')->andReturn(
        BackupTaskLog::factory()->create(['backup_task_id' => $this->backupTask->id])
    );
    $this->fileBackupTask->shouldReceive('updateBackupTaskLogOutput')->andReturnNull();
    $this->fileBackupTask->shouldReceive('logMessage')->andReturnNull();
});

afterEach(function (): void {
    Mockery::close();
});

test('perform backup successfully', function (): void {
    $this->sftpMock->shouldReceive('stat')->with(Mockery::pattern('/^\/path\/to\/backup/'))->andReturn(['size' => 1000]);
    $this->fileBackupTask->shouldReceive('checkPathExists')->andReturn(true);
    $this->fileBackupTask->shouldReceive('getRemoteDirectorySize')->andReturn(1000);
    $this->fileBackupTask->shouldReceive('isLaravelDirectory')->andReturn(false);
    $this->fileBackupTask->shouldReceive('zipRemoteDirectory')->andReturnNull();
    $this->fileBackupTask->shouldReceive('backupDestinationDriver')->andReturn(true);
    $this->fileBackupTask->shouldReceive('createBackupDestinationInstance')->andReturn($this->s3Mock);
    $this->fileBackupTask->shouldReceive('rotateOldBackups')->andReturnNull();

    $this->sftpMock->shouldReceive('delete')->andReturn(true);

    $this->fileBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('backup fails when source path does not exist', function (): void {
    $this->fileBackupTask->shouldReceive('checkPathExists')->andReturn(false);
    $this->fileBackupTask->shouldReceive('handleBackupFailure')->once();

    $this->fileBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('backup fails when directory size exceeds limit', function (): void {
    $this->fileBackupTask->shouldReceive('checkPathExists')->andReturn(true);
    $this->fileBackupTask->shouldReceive('getRemoteDirectorySize')->andReturn(BackupConstants::FILE_SIZE_LIMIT + 1);
    $this->fileBackupTask->shouldReceive('handleBackupFailure')->once();

    $this->fileBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('backup excludes node_modules and vendor for Laravel directories', function (): void {
    // Setup Laravel directory detection
    $this->sftpMock->shouldReceive('stat')->with('/path/to/backup/artisan')->andReturn(['type' => 1]);
    $this->sftpMock->shouldReceive('stat')->with('/path/to/backup/composer.json')->andReturn(['type' => 1]);
    $this->sftpMock->shouldReceive('stat')->with('/path/to/backup/package.json')->andReturn(['type' => 1]);

    $this->fileBackupTask->shouldReceive('checkPathExists')->andReturn(true);
    $this->fileBackupTask->shouldReceive('getRemoteDirectorySize')->andReturn(1000);
    $this->fileBackupTask->shouldReceive('isLaravelDirectory')->andReturn(true);
    $this->fileBackupTask->shouldReceive('getExcludedDirectories')
        ->andReturn(['node_modules/*', 'vendor/*']);
    $this->fileBackupTask->shouldReceive('zipRemoteDirectory')
        ->once()
        ->andReturnNull();
    $this->fileBackupTask->shouldReceive('backupDestinationDriver')->andReturn(true);

    $this->fileBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('backup fails when zipping throws an exception', function (): void {
    $this->fileBackupTask->shouldReceive('checkPathExists')->andReturn(true);
    $this->fileBackupTask->shouldReceive('getRemoteDirectorySize')->andReturn(1000);
    $this->fileBackupTask->shouldReceive('isLaravelDirectory')->andReturn(false);
    $this->fileBackupTask->shouldReceive('zipRemoteDirectory')->andThrow(new BackupTaskZipException('Failed to zip directory'));
    $this->fileBackupTask->shouldReceive('handleBackupFailure')->once();

    expect(fn () => $this->fileBackupTask->handle())
        ->toThrow(RuntimeException::class, 'Unexpected error during backup: Failed to zip directory');

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('backup fails when upload to destination fails', function (): void {
    $this->sftpMock->shouldReceive('stat')->with(Mockery::pattern('/^\/path\/to\/backup/'))->andReturn(['size' => 1000]);
    $this->fileBackupTask->shouldReceive('checkPathExists')->andReturn(true);
    $this->fileBackupTask->shouldReceive('getRemoteDirectorySize')->andReturn(1000);
    $this->fileBackupTask->shouldReceive('isLaravelDirectory')->andReturn(false);
    $this->fileBackupTask->shouldReceive('zipRemoteDirectory')->andReturnNull();
    $this->fileBackupTask->shouldReceive('backupDestinationDriver')->andReturn(false);
    $this->fileBackupTask->shouldReceive('handleBackupFailure')->once();

    $this->fileBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('backup with rotation', function (): void {
    $this->backupTask->update(['maximum_backups_to_keep' => 5]);

    $this->sftpMock->shouldReceive('stat')->with(Mockery::pattern('/^\/path\/to\/backup/'))->andReturn(['size' => 1000]);
    $this->fileBackupTask->shouldReceive('checkPathExists')->andReturn(true);
    $this->fileBackupTask->shouldReceive('getRemoteDirectorySize')->andReturn(1000);
    $this->fileBackupTask->shouldReceive('isLaravelDirectory')->andReturn(false);
    $this->fileBackupTask->shouldReceive('zipRemoteDirectory')->andReturnNull();
    $this->fileBackupTask->shouldReceive('backupDestinationDriver')->andReturn(true);
    $this->fileBackupTask->shouldReceive('createBackupDestinationInstance')->andReturn($this->s3Mock);
    $this->fileBackupTask->shouldReceive('rotateOldBackups')->once()->andReturnNull();

    $this->sftpMock->shouldReceive('delete')->andReturn(true);

    $this->fileBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('handle unexpected exception', function (): void {
    $this->fileBackupTask->shouldReceive('performBackup')->andThrow(new Exception('Unexpected error'));
    $this->fileBackupTask->shouldReceive('handleBackupFailure')->once();

    expect(fn () => $this->fileBackupTask->handle())
        ->toThrow(RuntimeException::class, 'Unexpected error during backup: Unexpected error');

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});
