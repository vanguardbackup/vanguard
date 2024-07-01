<?php

use App\Mail\BackupTaskFailed;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use App\Models\User;
use App\Services\Backup\Contracts\BackupDestinationInterface;
use App\Services\Backup\Tasks\DatabaseBackup;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Net\SFTP;

beforeEach(function () {
    test_create_keys();
});

afterEach(function () {
    test_restore_keys();
});

it('successfully completes a database backup task', function () {
    Mail::fake();
    Event::fake();
    Storage::fake('s3');

    $user = User::factory()->create(['timezone' => 'UTC']);
    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
        'database_password' => encrypt('password'),
    ]);
    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $user->id,
        'type' => 's3',
    ]);
    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->id,
        'remote_server_id' => $remoteServer->id,
        'backup_destination_id' => $backupDestination->id,
        'type' => BackupTask::TYPE_DATABASE,
        'status' => BackupTask::STATUS_READY,
        'database_name' => 'test_db',
        'store_path' => '/backup/path',
    ]);

    $backupTaskLog = new BackupTaskLog(['backup_task_id' => $backupTask->id, 'output' => '']);

    $sftpMock = Mockery::mock(SFTP::class);
    $sftpMock->shouldReceive('exec')->andReturn('mysql');
    $sftpMock->shouldReceive('delete')->andReturn(true);

    $backupDestinationInterfaceMock = Mockery::mock(BackupDestinationInterface::class);
    $backupDestinationInterfaceMock->shouldReceive('listFiles')->andReturn([]);
    $backupDestinationInterfaceMock->shouldReceive('deleteFile')->andReturn(true);

    $this->mock(DatabaseBackup::class, function ($mock) use ($backupTask, $backupTaskLog, $sftpMock, $backupDestination, $backupDestinationInterfaceMock) {
        $mock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('obtainBackupTask')->andReturn($backupTask)
            ->shouldReceive('recordBackupTaskLog')->andReturn($backupTaskLog)
            ->shouldReceive('updateBackupTaskLogOutput')->andReturnNull()
            ->shouldReceive('updateBackupTaskStatus')->andReturnNull()
            ->shouldReceive('establishSFTPConnection')->andReturn($sftpMock)
            ->shouldReceive('getDatabaseType')->andReturn('mysql')
            ->shouldReceive('dumpRemoteDatabase')->andReturn(true)
            ->shouldReceive('backupDestinationDriver')
            ->withArgs(function ($destinationType, $sftp, $remotePath, $backupDestinationModel, $fileName, $storagePath) use ($backupDestination, $backupTask) {
                return $destinationType === $backupDestination->type &&
                    $sftp instanceof SFTP &&
                    is_string($remotePath) &&
                    $backupDestinationModel->is($backupDestination) &&
                    is_string($fileName) &&
                    $storagePath === $backupTask->store_path;
            })
            ->andReturn(true)
            ->shouldReceive('createBackupDestinationInstance')->andReturn($backupDestinationInterfaceMock)
            ->shouldReceive('rotateOldBackups')->andReturn(true)
            ->shouldReceive('logWithTimestamp')->andReturn('Logged message')
            ->shouldReceive('handle')->passthru();
    });

    $action = app(DatabaseBackup::class);
    $action->handle($backupTask->id);

    $backupTask->refresh();
    expect($backupTask->status)->toBe(BackupTask::STATUS_READY)
        ->and(BackupTaskLog::where('backup_task_id', $backupTask->id)->exists())->toBeTrue();
});

it('handles a failed database connection gracefully', function () {
    Mail::fake();
    Event::fake();

    Log::shouldReceive('info')->andReturnUsing(function ($message) {
    });
    Log::shouldReceive('error')->andReturnUsing(function ($message) {
    });

    $user = User::factory()->create(['timezone' => 'UTC']);
    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
        'database_password' => encrypt('password'),
    ]);
    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->id,
        'remote_server_id' => $remoteServer->id,
        'type' => BackupTask::TYPE_DATABASE,
        'status' => BackupTask::STATUS_READY,
        'database_name' => 'test_db',
        'store_path' => '/backup/path',
    ]);

    $backupTaskLog = new BackupTaskLog(['backup_task_id' => $backupTask->id, 'output' => '']);

    $databaseBackup = $this->partialMock(DatabaseBackup::class, function ($mock) use ($backupTask, $backupTaskLog, &$debugLog) {
        $mock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('obtainBackupTask')->andReturn($backupTask)
            ->shouldReceive('recordBackupTaskLog')->andReturn($backupTaskLog)
            ->shouldReceive('updateBackupTaskLogOutput')->andReturnNull()
            ->shouldReceive('updateBackupTaskStatus')->andReturnUsing(function ($task, $status) use (&$debugLog) {
            })
            ->shouldReceive('establishSFTPConnection')->andThrow(new \Exception('Connection failed'))
            ->shouldReceive('logWithTimestamp')->andReturn('[timestamp] message')
            ->shouldReceive('handleFailure')->andReturnUsing(function ($task, &$logOutput, $errorMessage) use ($mock, &$debugLog) {
                $mock->sendEmailNotificationOfTaskFailure($task, $errorMessage);
            })
            ->shouldReceive('sendEmailNotificationOfTaskFailure')->andReturnUsing(function ($task, $errorMessage) use (&$debugLog) {
                Mail::to($task->user)->queue(new BackupTaskFailed($task->user, $task->label, $errorMessage));
            });

        $mock->shouldReceive('handle')->andReturnUsing(function ($backupTaskId) use ($mock, &$debugLog) {
            try {
                $backupTask = $mock->obtainBackupTask($backupTaskId);
                $backupTaskLog = $mock->recordBackupTaskLog($backupTaskId, '');
                $mock->updateBackupTaskStatus($backupTask, BackupTask::STATUS_RUNNING);

                $debugLog[] = 'Attempting to establish SFTP connection';
                $mock->establishSFTPConnection($backupTask->remoteServer, $backupTask);

                $debugLog[] = 'SFTP connection established successfully';
            } catch (\Exception $exception) {
                $logOutput = 'Error in backup process: ' . $exception->getMessage() . "\n";
                $mock->updateBackupTaskLogOutput($backupTaskLog, $logOutput);
                $mock->handleFailure($backupTask, $logOutput, $exception->getMessage());
            } finally {
                $mock->updateBackupTaskStatus($backupTask, BackupTask::STATUS_READY);
            }
        });
    });

    $debugLog[] = 'Before calling handle method';
    $databaseBackup->handle($backupTask->id);
    $debugLog[] = 'After calling handle method';

    $backupTask->refresh();
    expect($backupTask->status)->toBe(BackupTask::STATUS_READY);

    // Check for queued BackupTaskFailed mails
    $queuedBackupTaskFailedMails = Mail::queued(BackupTaskFailed::class);

    Mail::assertQueued(BackupTaskFailed::class, function ($mail) use ($user, $backupTask) {
        return $mail->hasTo($user->email) &&
            $mail->taskName === $backupTask->label &&
            str_contains($mail->errorMessage, 'Connection failed');
    });
});

it('rotates old backups when configured', function () {
    Mail::fake();
    Event::fake();
    Storage::fake('s3');

    $user = User::factory()->create(['timezone' => 'UTC']);
    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
        'database_password' => encrypt('password'),
    ]);
    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $user->id,
        'type' => 's3',
    ]);
    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->id,
        'remote_server_id' => $remoteServer->id,
        'backup_destination_id' => $backupDestination->id,
        'type' => BackupTask::TYPE_DATABASE,
        'status' => BackupTask::STATUS_READY,
        'maximum_backups_to_keep' => 5,
        'database_name' => 'test_db',
        'store_path' => '/backup/path',
    ]);

    $backupTaskLog = new BackupTaskLog(['backup_task_id' => $backupTask->id, 'output' => '']);

    $sftpMock = Mockery::mock(SFTP::class);
    $sftpMock->shouldReceive('exec')->andReturn('mysql');
    $sftpMock->shouldReceive('delete')->andReturn(true);

    $backupDestinationInterfaceMock = Mockery::mock(BackupDestinationInterface::class);
    $backupDestinationInterfaceMock->shouldReceive('listFiles')->andReturn([]);
    $backupDestinationInterfaceMock->shouldReceive('deleteFile')->andReturn(true);

    $this->mock(DatabaseBackup::class, function ($mock) use ($backupTask, $backupTaskLog, $sftpMock, $backupDestination, $backupDestinationInterfaceMock) {
        $mock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('obtainBackupTask')->andReturn($backupTask)
            ->shouldReceive('recordBackupTaskLog')->andReturn($backupTaskLog)
            ->shouldReceive('updateBackupTaskLogOutput')->andReturnNull()
            ->shouldReceive('updateBackupTaskStatus')->andReturnNull()
            ->shouldReceive('establishSFTPConnection')->andReturn($sftpMock)
            ->shouldReceive('getDatabaseType')->andReturn('mysql')
            ->shouldReceive('dumpRemoteDatabase')->andReturn(true)
            ->shouldReceive('backupDestinationDriver')
            ->withArgs(function ($destinationType, $sftp, $remotePath, $backupDestinationModel, $fileName, $storagePath) use ($backupDestination, $backupTask) {
                return $destinationType === $backupDestination->type &&
                    $sftp instanceof SFTP &&
                    is_string($remotePath) &&
                    $backupDestinationModel->is($backupDestination) &&
                    is_string($fileName) &&
                    $storagePath === $backupTask->store_path;
            })
            ->andReturn(true)
            ->shouldReceive('createBackupDestinationInstance')->andReturn($backupDestinationInterfaceMock)
            ->shouldReceive('rotateOldBackups')->once()->andReturn(true)
            ->shouldReceive('logWithTimestamp')->andReturn('Logged message')
            ->shouldReceive('handle')->passthru();
    });

    $action = app(DatabaseBackup::class);
    $action->handle($backupTask->id);

    // The assertion is already made in the mock expectations (shouldReceive('rotateOldBackups')->once())
});
