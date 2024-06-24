<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\BackupTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class UpcomingBackupTasks extends Component
{
    public Collection $scheduledBackupTasks; // @phpstan-ignore-line

    public function render(): View
    {
        $tasks = BackupTask::notPaused()->with('remoteServer')->get()->map(function ($task) {
            $nextRun = $task->calculateNextRun();

            return (object) [
                'task' => $task,
                'next_run' => $nextRun,
                'due_to_run' => optional($nextRun)
                    ->timezone(Auth::user()->timezone ?? config('app.timezone'))
                    ->format('l, d F Y H:i'),
            ];
        })->filter(function ($scheduledTask) {
            return ! is_null($scheduledTask->next_run);
        })->sortBy('next_run')->values()->take(20);

        $this->scheduledBackupTasks = $tasks;

        return view('livewire.dashboard.upcoming-backup-tasks', [
            'scheduledBackupTasks' => $tasks,
        ]);
    }
}
