<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BackupTask;
use Illuminate\Console\Command;

class ResetInoperativeBackupTasksCommand extends Command
{
    protected $signature = 'vanguard:reset-inoperative-backup-tasks';

    protected $description = 'Resets any backup tasks that are still running after a certain period of time.';

    public function handle(): void
    {
        $timeoutPeriod = 30 * 60; // 30 minutes

        $tasks = BackupTask::where('status', BackupTask::STATUS_RUNNING)
            ->where('last_script_update_at', '<', now()->subSeconds($timeoutPeriod))
            ->get();

        $tasks->each(function (BackupTask $task) {
            $task->markAsReady();
            $task->resetScriptUpdateTime();
            $this->info("Resetting backup task with ID {$task->getAttribute('id')}.");
        });
    }
}
