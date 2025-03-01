<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Actions\LoadScheduledBackupTasksAction;
use App\Models\BackupTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Manages the display of upcoming backup tasks on the dashboard.
 */
class UpcomingBackupTasks extends Component
{
    /**
     * The collection of scheduled backup tasks.
     *
     * @var Collection<int, object{task: BackupTask, next_run: ?Carbon, due_to_run: ?string, type: string}>
     */
    public Collection $scheduledBackupTasks;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->scheduledBackupTasks = collect();
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        $this->loadScheduledBackupTasks();

        return view('livewire.dashboard.upcoming-backup-tasks')->with([
            'scheduledBackupTasks' => $this->scheduledBackupTasks,
        ]);
    }

    /**
     * Load the scheduled backup tasks.
     */
    private function loadScheduledBackupTasks(): void
    {
        $this->scheduledBackupTasks = app(LoadScheduledBackupTasksAction::class)->execute();
    }
}
