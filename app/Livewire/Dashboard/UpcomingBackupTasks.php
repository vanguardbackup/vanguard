<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\BackupTask;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Manages the display of upcoming backup tasks on the dashboard.
 */
class UpcomingBackupTasks extends Component
{
    /** @var Collection<int, object{task: BackupTask, next_run: ?Carbon, due_to_run: ?string, type: string}> */
    public Collection $scheduledBackupTasks;

    public function mount(): void
    {
        $this->scheduledBackupTasks = collect();
    }

    public function render(): View
    {
        $this->loadScheduledBackupTasks();

        return view('livewire.dashboard.upcoming-backup-tasks', [
            'scheduledBackupTasks' => $this->scheduledBackupTasks,
        ]);
    }

    private function loadScheduledBackupTasks(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $locale = $user->language ?? config('app.locale');
        $timezone = $user->timezone ?? config('app.timezone');

        Carbon::setLocale($locale);

        $this->scheduledBackupTasks = BackupTask::notPaused()
            ->with('remoteServer')
            ->get()
            ->map(fn (BackupTask $backupTask): object => $this->formatBackupTask($backupTask, $timezone, $locale))
            ->filter(fn (object $scheduledTask): bool => $scheduledTask->next_run !== null)
            ->sortBy('next_run')
            ->values()
            ->take(20);
    }

    /**
     * @return object{task: BackupTask, next_run: ?Carbon, due_to_run: ?string, type: string}
     */
    private function formatBackupTask(BackupTask $backupTask, string $timezone, string $locale): object
    {
        $nextRun = $backupTask->calculateNextRun();

        $dueToRun = null;
        if ($nextRun instanceof \Illuminate\Support\Carbon) {
            $nextRunLocalized = $nextRun->timezone($timezone)->locale($locale);
            $dueToRun = ucfirst($nextRunLocalized->isoFormat('dddd, D MMMM YYYY HH:mm'));
        }

        return (object) [
            'task' => $backupTask,
            'next_run' => $nextRun,
            'due_to_run' => $dueToRun,
            'type' => ucfirst(__($backupTask->getAttribute('type'))),
        ];
    }
}
