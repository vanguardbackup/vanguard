<?php

declare(strict_types=1);

use App\Jobs\BackupTasks\SendTelegramNotificationJob;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

it('to be pushed to a queue', function (): void {

    $backup_task = BackupTask::factory()->create();
    $backup_task_log = BackupTaskLog::factory()->create();
    Queue::fake();
    Queue::assertNothingPushed();
    SendTelegramNotificationJob::dispatch($backup_task, $backup_task_log, 'TelegramID');
    Queue::assertPushed(SendTelegramNotificationJob::class, 1);
    Queue::assertPushed(function (SendTelegramNotificationJob $sendTelegramNotificationJob) use ($backup_task, $backup_task_log): bool {
        return $sendTelegramNotificationJob->notificationStreamValue === 'TelegramID' && $sendTelegramNotificationJob->backupTaskLog === $backup_task_log && $sendTelegramNotificationJob->backupTask === $backup_task;
    });
});

it('calls the sendTelegramNotification method', function (): void {
    /**
     * @var BackupTask|MockInterface $mock
     */
    $mock = Mockery::mock(BackupTask::class);
    $backup_task_log = BackupTaskLog::factory()->create();
    $mock->shouldReceive('sendTelegramNotification')->with($backup_task_log, 'TelegramID')->once();
    (new SendTelegramNotificationJob($mock, $backup_task_log, 'TelegramID'))->handle();
});
