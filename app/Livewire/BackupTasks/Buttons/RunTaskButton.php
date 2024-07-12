<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Buttons;

use App\Models\BackupTask;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class RunTaskButton extends Component
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
            "echo-private:backup-tasks.{$this->backupTask->getAttribute('id')},BackupTaskStatusChanged" => 'refreshSelf',
            "update-run-button-{$this->backupTask->getAttribute('id')}" => 'refreshSelf',
            "pause-button-clicked-{$this->backupTask->getAttribute('id')}" => 'refreshSelf',
        ];
    }

    public function refreshSelf(): void
    {
        $this->dispatch('$refresh');
    }

    public function runTask(): void
    {
        $this->backupTask->markAsRunning();

        $this->backupTask->run();

        $this->dispatch('task-button-clicked-' . $this->backupTask->getAttribute('id'));

        $this->dispatch('refresh-backup-tasks-table');

        Toaster::success(__('Backup task is running.'));
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.buttons.run-task-button');
    }
}
