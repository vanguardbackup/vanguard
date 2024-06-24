<?php

namespace App\Livewire\BackupTasks;

use App\Models\BackupTaskLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ClearLogButton extends Component
{
    public function clearAllLogs(): void
    {
        BackupTaskLog::whereHas('backupTask', function ($query) {
            $query->where('user_id', Auth::id());
        })->delete();

        Toaster::success('All your backup task logs have been removed.');

        $this->dispatch('refreshBackupTaskHistory');
        $this->dispatch('close-modal', 'clear-all-backup-task-logs');
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.clear-log-button');
    }

    /**
     * Get the listeners array.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'refreshBackupTaskHistory' => '$refresh',
        ];
    }
}
