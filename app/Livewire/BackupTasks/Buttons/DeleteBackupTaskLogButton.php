<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Buttons;

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
        $this->authorize('forceDelete', $this->backupTaskLog->getAttribute('backupTask'));

        $this->backupTaskLog->forceDelete();

        Toaster::success(__('Backup task log has been removed.'));

        $this->dispatch('refreshBackupTaskHistory');

        return Redirect::route('backup-tasks.index');
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.buttons.delete-backup-task-log-button');
    }
}
