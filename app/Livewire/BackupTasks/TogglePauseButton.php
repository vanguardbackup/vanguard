<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks;

use App\Models\BackupTask;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class TogglePauseButton extends Component
{
    public BackupTask $backupTask;

    /**
     * Get the listeners array.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            "task-button-clicked-{$this->backupTask->id}" => 'refreshSelf',
        ];
    }

    public function refreshSelf(): void
    {
        $this->dispatch('$refresh');
    }

    public function togglePauseState(): void
    {
        if ($this->backupTask->isPaused()) {
            $this->backupTask->resume();
            Toaster::success(__('Backup task has been resumed.'));
        } else {
            Toaster::success(__('Backup task has been paused.'));
            $this->backupTask->pause();
        }

        $this->dispatch('pause-button-clicked-' . $this->backupTask->id);
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.toggle-pause-button');
    }
}
