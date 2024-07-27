<?php

declare(strict_types=1);

use App\Exceptions\DatabaseDumpException;
use App\Exceptions\SFTPConnectionException;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use App\Services\Backup\Adapters\SFTPAdapter;
use App\Services\Backup\BackupConstants;
use App\Services\Backup\Destinations\S3;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Services\Backup\Tasks\Helpers\DBBackupTaskTestClass;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Event::fake();
    $this->remoteServer = RemoteServer::factory()->create([
        'database_password' => encrypt('testpassword'),
    ]);

    $this->backupDestination = BackupDestination::factory()->create([
        'type' => BackupConstants::DRIVER_S3,
    ]);

    $this->backupTask = BackupTask::factory()->create([
        'remote_server_id' => $this->remoteServer->id,
        'backup_destination_id' => $this->backupDestination->id,
        'database_name' => 'testdb',
        'store_path' => '/backups',
    ]);

    $this->sftpMock = Mockery::mock(SFTPAdapter::class);
    $this->sftpMock->shouldReceive('isConnected')->andReturn(true);
    $this->sftpMock->shouldReceive('exec')->andReturn(''); // Default behavior
    $this->s3Mock = Mockery::mock(S3::class);

    $this->databaseBackupTask = Mockery::mock(DBBackupTaskTestClass::class, [$this->backupTask->id])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $this->databaseBackupTask->shouldReceive('establishSFTPConnection')->andReturn($this->sftpMock);
    $this->databaseBackupTask->shouldReceive('recordBackupTaskLog')->andReturn(
        BackupTaskLog::factory()->create(['backup_task_id' => $this->backupTask->id])
    );
    $this->databaseBackupTask->shouldReceive('updateBackupTaskLogOutput')->andReturnNull();
    $this->databaseBackupTask->shouldReceive('logMessage')->andReturnNull();
});

afterEach(function (): void {
    Mockery::close();
});

test('perform backup successfully', function (): void {
    $this->sftpMock->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/mysqldump/'))->andReturn('');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/test -s/'))->andReturn('exists');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/cat/'))->andReturn('-- MySQL dump');
    $this->sftpMock->shouldReceive('delete')->andReturn(true);

    $this->databaseBackupTask->shouldReceive('backupDestinationDriver')->andReturn(true);
    $this->databaseBackupTask->shouldReceive('getDatabaseType')->andReturn(BackupConstants::DATABASE_TYPE_MYSQL);
    $this->databaseBackupTask->shouldReceive('dumpRemoteDatabase')->andReturnNull();
    $this->databaseBackupTask->shouldReceive('createBackupDestinationInstance')->andReturn($this->s3Mock);
    $this->databaseBackupTask->shouldReceive('getRemoteDirectorySize')->andReturn('500');
    $this->databaseBackupTask->shouldReceive('rotateOldBackups')->andReturnNull();

    $this->databaseBackupTask->shouldReceive('updateBackupTaskStatus')->twice();

    $this->databaseBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('perform backup fails due to missing database password', function (): void {
    $this->remoteServer->update(['database_password' => null]);
    $this->databaseBackupTask->shouldReceive('handleBackupFailure')->once();

    $this->databaseBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('perform backup fails due to database dump exception', function (): void {
    $this->sftpMock->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');
    $this->databaseBackupTask->shouldReceive('getDatabaseType')->andReturn(BackupConstants::DATABASE_TYPE_MYSQL);
    $this->databaseBackupTask->shouldReceive('dumpRemoteDatabase')->andThrow(new DatabaseDumpException('Failed to dump the database'));
    $this->databaseBackupTask->shouldReceive('handleBackupFailure')->once();

    $this->databaseBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('perform backup fails due to SFTP connection exception', function (): void {
    $this->databaseBackupTask->shouldReceive('establishSFTPConnection')
        ->andThrow(new SFTPConnectionException('Failed to establish SFTP connection'));
    $this->databaseBackupTask->shouldReceive('handleBackupFailure')->once();

    $this->databaseBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('perform backup fails due to backup destination driver failure', function (): void {
    $this->sftpMock->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');
    $this->databaseBackupTask->shouldReceive('getDatabaseType')->andReturn(BackupConstants::DATABASE_TYPE_MYSQL);
    $this->databaseBackupTask->shouldReceive('dumpRemoteDatabase')->andReturnNull();
    $this->databaseBackupTask->shouldReceive('backupDestinationDriver')->andReturn(false);
    $this->databaseBackupTask->shouldReceive('handleBackupFailure')->once();

    $this->databaseBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('perform backup with backup rotation', function (): void {
    $this->backupTask->update(['maximum_backups_to_keep' => 5]);

    $this->sftpMock->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/mysqldump/'))->andReturn('');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/test -s/'))->andReturn('exists');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/cat/'))->andReturn('-- MySQL dump');
    $this->sftpMock->shouldReceive('delete')->andReturn(true);

    $this->databaseBackupTask->shouldReceive('backupDestinationDriver')->andReturn(true);
    $this->databaseBackupTask->shouldReceive('getDatabaseType')->andReturn(BackupConstants::DATABASE_TYPE_MYSQL);
    $this->databaseBackupTask->shouldReceive('dumpRemoteDatabase')->andReturnNull();
    $this->databaseBackupTask->shouldReceive('createBackupDestinationInstance')->andReturn($this->s3Mock);
    $this->databaseBackupTask->shouldReceive('rotateOldBackups')->once()->andReturnNull();
    $this->databaseBackupTask->shouldReceive('getRemoteDatabaseSize')->andReturn('500');

    $this->databaseBackupTask->shouldReceive('updateBackupTaskStatus')->twice();

    $this->databaseBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('perform backup with PostgreSQL database', function (): void {
    $this->sftpMock->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql: command not found');
    $this->sftpMock->shouldReceive('exec')->with('psql --version 2>&1')->andReturn('psql (PostgreSQL) 13.4');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/PGPASSWORD=.*pg_dump/'))->andReturn('');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/test -s/'))->andReturn('exists');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/cat/'))->andReturn('-- PostgreSQL dump');
    $this->sftpMock->shouldReceive('delete')->andReturn(true);

    $this->databaseBackupTask->shouldReceive('backupDestinationDriver')->andReturn(true);
    $this->databaseBackupTask->shouldReceive('dumpRemoteDatabase')->andReturnNull();
    $this->databaseBackupTask->shouldReceive('createBackupDestinationInstance')->andReturn($this->s3Mock);

    $this->databaseBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('perform backup with excluded tables', function (): void {
    $this->backupTask->update(['excluded_database_tables' => 'table1,table2']);

    $this->sftpMock->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/mysqldump.*--ignore-table=testdb.table1.*--ignore-table=testdb.table2/'))->andReturn('');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/test -s/'))->andReturn('exists');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/cat/'))->andReturn('-- MySQL dump');
    $this->sftpMock->shouldReceive('delete')->andReturn(true);

    $this->databaseBackupTask->shouldReceive('backupDestinationDriver')->andReturn(true);
    $this->databaseBackupTask->shouldReceive('getDatabaseType')->andReturn(BackupConstants::DATABASE_TYPE_MYSQL);
    $this->databaseBackupTask->shouldReceive('createBackupDestinationInstance')->andReturn($this->s3Mock);

    $this->databaseBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('perform backup fails when dump file is empty', function (): void {
    $this->sftpMock->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/mysqldump/'))->andReturn('');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/test -s/'))->andReturn('not exists');

    $this->databaseBackupTask->shouldReceive('getDatabaseType')->andReturn(BackupConstants::DATABASE_TYPE_MYSQL);
    $this->databaseBackupTask->shouldReceive('handleBackupFailure')->once();

    $this->databaseBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('perform backup with isolated credentials', function (): void {
    $this->backupTask->update(['isolated_username' => 'isolated_user']);

    $this->sftpMock->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/mysqldump/'))->andReturn('');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/test -s/'))->andReturn('exists');
    $this->sftpMock->shouldReceive('exec')->with(Mockery::pattern('/cat/'))->andReturn('-- MySQL dump');
    $this->sftpMock->shouldReceive('delete')->andReturn(true);

    $this->databaseBackupTask->shouldReceive('backupDestinationDriver')->andReturn(true);
    $this->databaseBackupTask->shouldReceive('getDatabaseType')->andReturn(BackupConstants::DATABASE_TYPE_MYSQL);
    $this->databaseBackupTask->shouldReceive('dumpRemoteDatabase')->andReturnNull();
    $this->databaseBackupTask->shouldReceive('createBackupDestinationInstance')->andReturn($this->s3Mock);

    $this->databaseBackupTask->handle();

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});

test('generate backup file name', function (): void {
    // Test without appended file name
    $fileName = $this->databaseBackupTask->generateBackupFileName('sql');
    expect($fileName)->toMatch('/^backup_\d+_\d{14}\.sql$/');

    // Test with appended file name
    $this->backupTask->update(['appended_file_name' => 'custom']);

    // Create a new mock instance for this specific scenario
    $legacyMock = Mockery::mock(DBBackupTaskTestClass::class, [$this->backupTask->id])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $legacyMock->shouldReceive('hasFileNameAppended')->andReturn(true);

    $fileNameWithAppend = $legacyMock->generateBackupFileName('sql');
    expect($fileNameWithAppend)->toMatch('/^custom_backup_\d+_\d{14}\.sql$/');
});

test('handle unexpected exception', function (): void {
    $this->databaseBackupTask->shouldReceive('performBackup')->andThrow(new Exception('Unexpected error'));
    $this->databaseBackupTask->shouldReceive('handleBackupFailure')->once();

    expect(fn () => $this->databaseBackupTask->handle())
        ->toThrow(RuntimeException::class, 'Unexpected error during backup: Unexpected error');

    $this->assertDatabaseHas('backup_tasks', [
        'id' => $this->backupTask->id,
        'status' => BackupTask::STATUS_READY,
    ]);
});
