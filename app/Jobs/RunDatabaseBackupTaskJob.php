<?php

namespace App\Jobs;

use App\Services\Backup\Tasks\DatabaseBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunDatabaseBackupTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes

    public function __construct(public int $backupTaskId)
    {
        //
    }

    public function handle(): void
    {
        $action = new DatabaseBackup;
        $action->handle($this->backupTaskId);
    }
}
