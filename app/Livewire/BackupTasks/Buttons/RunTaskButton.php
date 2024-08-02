<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Buttons;

use App\Models\BackupTask;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Manages the button for running a backup task.
 *
 * This component handles the UI and logic for initiating a backup task
 * and updating its status.
 */
class RunTaskButton extends Component
{
    /** @var BackupTask The backup task to be run */
    public BackupTask $backupTask;

    /**
     * Refresh the component.
     */
    public function refreshSelf(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Run the backup task.
     *
     * Marks the task as running, initiates the backup process,
     * and dispatches events to update related components.
     */
    public function runTask(): void
    {
        $this->backupTask->markAsRunning();

        $this->backupTask->run();

        $this->dispatch('task-button-clicked-' . $this->backupTask->getAttribute('id'));

        $this->dispatch('refresh-backup-tasks-table');

        Toaster::success('Backup task is running.');
    }

    /**
     * Render the run task button component.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.buttons.run-task-button');
    }

    /**
     * Get the event listeners for the component.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:backup-tasks.%s,BackupTaskStatusChanged', $this->backupTask->getAttribute('id')) => 'refreshSelf',
            'update-run-button-' . $this->backupTask->getAttribute('id') => 'refreshSelf',
            'pause-button-clicked-' . $this->backupTask->getAttribute('id') => 'refreshSelf',
            'refresh-backup-tasks-table' => 'refreshSelf', // Refresh everything!
        ];
    }
}
