<?php

declare(strict_types=1);

namespace App\Livewire\BackupDestinations;

use App\Models\BackupDestination;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;
use Override;

/**
 * Livewire component for checking the connection to a backup destination.
 *
 * This component provides functionality to initiate and refresh the connection check status.
 */
class CheckConnectionButton extends Component
{
    public BackupDestination $backupDestination;

    /**
     * Refresh the component.
     */
    public function refreshSelf(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Initiate the connection check for the backup destination.
     *
     * This method refreshes the backup destination, marks it as checking,
     * runs the check, and dispatches a notification.
     */
    public function checkConnection(): void
    {
        $this->backupDestination->refresh();

        $this->backupDestination->markAsChecking();

        $this->backupDestination->run();

        Toaster::info('Performing a connectivity check.');

        $this->dispatch('backup-destination-connection-check-initiated-' . $this->backupDestination->getAttribute('id'));
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.backup-destinations.check-connection-button');
    }

    /**
     * Get the listeners array.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:backup-destinations.%s,BackupDestinationConnectionCheck', $this->backupDestination->getAttribute('id')) => 'refreshSelf',
            'update-backup-destination-check-button-' . $this->backupDestination->getAttribute('id') => '$refresh',
        ];
    }
}
