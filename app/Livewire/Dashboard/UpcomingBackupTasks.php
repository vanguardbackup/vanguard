<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\BackupTask;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class UpcomingBackupTasks extends Component
{
    public Collection $scheduledBackupTasks; // @phpstan-ignore-line

    public function render(): View
    {
        $user = Auth::user();
        $locale = $user->language ?? config('app.locale');
        $timezone = $user->timezone ?? config('app.timezone');

        Carbon::setLocale($locale);

        $tasks = BackupTask::notPaused()->with('remoteServer')->get()->map(function ($task) use ($timezone, $locale) {
            $nextRun = $task->calculateNextRun();

            if ($nextRun) {
                $nextRunLocalized = $nextRun->timezone($timezone)->locale($locale);
                $dueToRun = $nextRunLocalized->isoFormat('dddd, D MMMM YYYY HH:mm');
            } else {
                $dueToRun = null;
            }

            return (object) [
                'task' => $task,
                'next_run' => $nextRun,
                'due_to_run' => isset($dueToRun) ? ucfirst($dueToRun) : null,
                'type' => ucfirst(__($task->type)),
            ];
        })->filter(function ($scheduledTask): bool {
            return ! is_null($scheduledTask->next_run);
        })->sortBy('next_run')->values()->take(20);

        $this->scheduledBackupTasks = $tasks;

        return view('livewire.dashboard.upcoming-backup-tasks', [
            'scheduledBackupTasks' => $tasks,
        ]);
    }
}
