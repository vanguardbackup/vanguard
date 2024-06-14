<?php

use App\Events\BackupTaskStatusChanged;
use App\Events\CreatedBackupTaskLog;
use App\Events\StreamBackupTaskLogEvent;
use App\Exceptions\BackupTaskRuntimeException;
use App\Mail\BackupTaskFailed;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Services\Backup\Backup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use phpseclib3\Net\SFTP;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->backupService = Mockery::mock(Backup::class)->makePartial()->shouldAllowMockingProtectedMethods();
});

test('validateConfiguration throws exception if SSH passphrase is not set', function () {
    Config::set('app.ssh.passphrase', null);
    $this->backupService->shouldReceive('logCritical')->once();

    expect(fn () => $this->backupService->validateConfiguration())
        ->toThrow(BackupTaskRuntimeException::class, 'The SSH passphrase is not set in the configuration.');
});

test('obtainBackupTask returns backup task', function () {
    $backupTask = BackupTask::factory()->create();

    $this->backupService->shouldReceive('logInfo')->once();
    $result = $this->backupService->obtainBackupTask($backupTask->id);

    expect($result->id)->toBe($backupTask->id);
});

test('recordBackupTaskLog creates and dispatches event', function () {
    Event::fake();
    $backupTask = BackupTask::factory()->create();
    $logOutput = 'Sample log output';

    $this->backupService->shouldReceive('logInfo')->once();
    $this->backupService->shouldReceive('logDebug')->once();

    $backupTaskLog = $this->backupService->recordBackupTaskLog($backupTask->id, $logOutput);

    expect($backupTaskLog->backup_task_id)->toBe($backupTask->id);
    expect($backupTaskLog->output)->toBe($logOutput);
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

afterEach(function () {
    Mockery::close();
});
