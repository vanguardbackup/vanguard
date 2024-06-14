<?php

namespace App\Livewire\BackupTasks;

use App\Models\BackupTaskLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class DeleteBackupTaskLogButton extends Component
{
    public BackupTaskLog $backupTaskLog;

    public function mount(BackupTaskLog $backupTaskLog): void
    {
        $this->backupTaskLog = $backupTaskLog;
    }

    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->backupTaskLog->backupTask);

        $this->backupTaskLog->forceDelete();

        Toaster::success('Backup task log has been removed.');

        $this->dispatch('refreshBackupTaskHistory');

        return Redirect::route('backup-tasks.index');
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.delete-backup-task-log-button');
    }
}
