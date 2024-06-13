<?php

namespace App\Livewire\BackupTasks;

use App\Models\BackupTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

class DeleteBackupTaskForm extends Component
{
    public BackupTask $backupTask;

    public function mount(BackupTask $backupTask): void
    {
        $this->backupTask = $backupTask;
    }

    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->backupTask);

        $this->backupTask->forceDelete();

        Toaster::success('Backup task has been removed.');

        return Redirect::route('backup-tasks.index');
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.delete-backup-task-form');
    }
}
