<?php

namespace App\Livewire\BackupTasks;

use App\Models\BackupTask;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class RunTaskButton extends Component
{
    public BackupTask $backupTask;

    public function getListeners(): array
    {
        return [
            "echo:backup-tasks.{$this->backupTask->id},BackupTaskStatusChanged" => 'refreshSelf',
            "update-run-button-{$this->backupTask->id}" => 'refreshSelf',
            "pause-button-clicked-{$this->backupTask->id}" => 'refreshSelf',
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

        $this->dispatch('task-button-clicked-' . $this->backupTask->id);

        $this->dispatch('refresh-backup-tasks-table');

        Toaster::success(__('Backup task is running.'));
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.run-task-button');
    }
}
