<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Backup\Tasks\FileBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunFileBackupTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes

    public function __construct(public int $backupTaskId)
    {
        //
    }

    public function handle(): void
    {
        $action = new FileBackup;
        $action->handle($this->backupTaskId);
    }
}
