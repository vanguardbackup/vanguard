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

    /**
     * Get the listeners array.
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
