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

/**
 * Manages the button for deleting a specific backup task log.
 *
 * This component handles the UI and logic for removing a single backup task log entry.
 */
class DeleteBackupTaskLogButton extends Component
{
    /** @var BackupTaskLog The backup task log to be deleted */
    public BackupTaskLog $backupTaskLog;

    /**
     * Initialize the component with a backup task log.
     */
    public function mount(BackupTaskLog $backupTaskLog): void
    {
        $this->backupTaskLog = $backupTaskLog;
    }

    /**
     * Delete the backup task log.
     *
     * Authorizes the action, deletes the log, and redirects to the index page.
     */
    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->backupTaskLog->getAttribute('backupTask'));

        $this->backupTaskLog->forceDelete();

        Toaster::success('Backup task log has been removed.');

        $this->dispatch('refreshBackupTaskHistory');

        return Redirect::route('backup-tasks.index');
    }

    /**
     * Render the delete backup task log button component.
     */
    public function render(): View
    {
        return view('livewire.backup-tasks.buttons.delete-backup-task-log-button');
    }
}
