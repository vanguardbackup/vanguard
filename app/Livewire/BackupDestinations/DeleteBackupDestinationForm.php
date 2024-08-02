<?php

declare(strict_types=1);

namespace App\Livewire\BackupDestinations;

use App\Models\BackupDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

/**
 * Livewire component for deleting a backup destination.
 *
 * This component handles the deletion of a backup destination and redirects
 * the user after successful deletion.
 */
class DeleteBackupDestinationForm extends Component
{
    public BackupDestination $backupDestination;

    /**
     * Initialize the component with the given backup destination.
     */
    public function mount(BackupDestination $backupDestination): void
    {
        $this->backupDestination = $backupDestination;
    }

    /**
     * Delete the backup destination.
     *
     * This method authorizes the action, force deletes the backup destination,
     * shows a success message, and redirects to the index page.
     */
    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->backupDestination);

        $this->backupDestination->forceDelete();

        Toaster::success(__('Backup destination has been removed.'));

        return Redirect::route('backup-destinations.index');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.backup-destinations.delete-backup-destination-form');
    }
}
