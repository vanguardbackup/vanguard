<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Buttons;

use App\Models\BackupTask;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Manages the button for toggling the pause state of a backup task.
 *
 * This component handles the UI and logic for pausing and resuming a backup task.
 */
class TogglePauseButton extends Component
{
    /** @var BackupTask The backup task to be toggled */
    public BackupTask $backupTask;

    /**
     * Refresh the component.
     */
    public function refreshSelf(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Toggle the pause state of the backup task.
     *
     * Pauses or resumes the task based on its current state and displays a success message.
     */
    public function togglePauseState(): void
    {
        if ($this->backupTask->isPaused()) {
            $this->backupTask->resume();
            Toaster::success('Backup task has been resumed.');
        } else {
            Toaster::success('Backup task has been paused.');
            $this->backupTask->pause();
        }

        $this->dispatch('pause-button-clicked-' . $this->backupTask->getAttribute('id'));
    }

    /**
     * Render the toggle pause button component.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.buttons.toggle-pause-button');
    }

    /**
     * Get the event listeners for the component.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'task-button-clicked-' . $this->backupTask->getAttribute('id') => 'refreshSelf',
            sprintf('echo-private:backup-tasks.%s,BackupTaskStatusChanged', $this->backupTask->getAttribute('id')) => 'refreshSelf',
        ];
    }
}
