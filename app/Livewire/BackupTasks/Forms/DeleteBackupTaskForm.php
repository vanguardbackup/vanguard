<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Forms;

use App\Models\BackupTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

/**
 * Manages the form for deleting a backup task.
 *
 * This component handles the UI and logic for permanently removing a backup task.
 */
class DeleteBackupTaskForm extends Component
{
    /** @var BackupTask The backup task to be deleted */
    public BackupTask $backupTask;

    /**
     * Initialize the component with a backup task.
     */
    public function mount(BackupTask $backupTask): void
    {
        $this->backupTask = $backupTask;
    }

    /**
     * Delete the backup task.
     *
     * Authorizes the action, deletes the task, and redirects to the index page.
     */
    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->backupTask);

        $this->backupTask->forceDelete();

        Toaster::success('Backup task has been removed.');

        return Redirect::route('backup-tasks.index');
    }

    /**
     * Render the delete backup task form.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.forms.delete-backup-task-form');
    }
}
