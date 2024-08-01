<?php

declare(strict_types=1);

namespace App\Jobs\BackupTasks;

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to send Slack notifications for backup tasks.
 *
 * This job is responsible for dispatching Slack webhook notifications
 * related to backup tasks and their logs.
 */
class SendSlackNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  BackupTask  $backupTask  The backup task associated with the notification
     * @param  BackupTaskLog  $backupTaskLog  The log entry for the backup task
     * @param  string  $notificationStreamValue  The value of the notification stream
     */
    public function __construct(
        public BackupTask $backupTask,
        public BackupTaskLog $backupTaskLog,
        public string $notificationStreamValue
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * Sends a Slack webhook notification for the backup task.
     */
    public function handle(): void
    {
        $this->backupTask->sendSlackWebhookNotification($this->backupTaskLog, $this->notificationStreamValue);
    }
}
