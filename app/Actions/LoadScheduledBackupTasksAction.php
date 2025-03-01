<?php

namespace App\Actions;

use App\Models\BackupTask;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Loads scheduled backup tasks for the authenticated user.
 */
class LoadScheduledBackupTasksAction
{
    /**
     * Execute the action to load scheduled backup tasks.
     *
     * @return Collection<int, object{task: BackupTask, next_run: ?Carbon, due_to_run: ?string, type: string}>
     */
    public function execute(): Collection
    {
        $user = Auth::user();
        if (! $user) {
            return collect();
        }

        $locale = $user->language ?? Config::get('app.locale');
        $timezone = $user->timezone ?? Config::get('app.timezone');

        Carbon::setLocale($locale);

        /** @var Collection<int, object{task: BackupTask, next_run: ?Carbon, due_to_run: ?string, type: string}> */
        return BackupTask::notPaused()
            ->with('remoteServer')
            ->get()
            ->map(function (BackupTask $backupTask) use ($timezone, $locale) {
                try {
                    $nextRun = $backupTask->calculateNextRun();

                    $dueToRun = null;
                    if ($nextRun instanceof Carbon) {
                        $nextRunLocalized = $nextRun->timezone($timezone)->locale($locale);
                        $dueToRun = ucfirst($nextRunLocalized->isoFormat('dddd, D MMMM YYYY HH:mm'));
                    }

                    /** @var object{task: BackupTask, next_run: ?Carbon, due_to_run: ?string, type: string} */
                    return (object) [
                        'task' => $backupTask,
                        'next_run' => $nextRun,
                        'due_to_run' => $dueToRun,
                        'type' => ucfirst($backupTask->getAttribute('type')),
                    ];
                } catch (Exception $e) {
                    Log::error('Failed to calculate next run for backup task', [
                        'backup_task_id' => $backupTask->getKey(),
                        'error' => $e->getMessage(),
                    ]);

                    /** @var object{task: BackupTask, next_run: ?Carbon, due_to_run: ?string, type: string} */
                    return (object) [
                        'task' => $backupTask,
                        'next_run' => null,
                        'due_to_run' => null,
                        'type' => ucfirst($backupTask->getAttribute('type')),
                    ];
                }
            })
            ->filter(fn (object $scheduledTask): bool => $scheduledTask->next_run !== null)
            ->sortBy('next_run')
            ->values()
            ->take(20);
    }
}
