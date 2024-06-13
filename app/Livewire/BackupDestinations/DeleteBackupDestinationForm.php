<?php

namespace App\Livewire\BackupDestinations;

use App\Models\BackupDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

class DeleteBackupDestinationForm extends Component
{
    public BackupDestination $backupDestination;

    public function mount(BackupDestination $backupDestination): void
    {
        $this->backupDestination = $backupDestination;
    }

    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->backupDestination);

        $this->backupDestination->forceDelete();

        Toaster::success('Backup destination has been removed.');

        return Redirect::route('backup-destinations.index');
    }

    public function render(): View
    {
        return view('livewire.backup-destinations.delete-backup-destination-form');
    }
}
