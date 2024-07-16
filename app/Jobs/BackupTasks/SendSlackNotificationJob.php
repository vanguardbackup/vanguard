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

class SendSlackNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public BackupTask $backupTask, public BackupTaskLog $backupTaskLog)
    {
        //
    }

    public function handle(): void
    {
        $this->backupTask->sendSlackWebhookNotification($this->backupTaskLog);
    }
}
