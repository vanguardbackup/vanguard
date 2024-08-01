<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BackupTask;
use Illuminate\Console\Command;

/**
 * Command to reset backup tasks that have been running for too long.
 *
 * This command identifies and resets backup tasks that have exceeded
 * a specified timeout period, marking them as ready for a new run.
 */
class ResetInoperativeBackupTasksCommand extends Command
{
    protected $signature = 'vanguard:reset-inoperative-backup-tasks';

    protected $description = 'Resets any backup tasks that are still running after a certain period of time.';

    /**
     * Execute the console command.
     *
     * This method fetches all backup tasks that have been running for longer
     * than the specified timeout period, resets them to a ready state,
     * and logs the action for each task.
     */
    public function handle(): void
    {
        $timeoutPeriod = 30 * 60; // 30 minutes

        $tasks = BackupTask::where('status', BackupTask::STATUS_RUNNING)
            ->where('last_script_update_at', '<', now()->subSeconds($timeoutPeriod))
            ->get();

        $tasks->each(function (BackupTask $backupTask): void {
            $backupTask->markAsReady();
            $backupTask->resetScriptUpdateTime();
            $this->info(sprintf('Resetting backup task with ID %s.', $backupTask->getAttribute('id')));
        });
    }
}
