<?php

declare(strict_types=1);

namespace App\Livewire\BackupDestinations;

use Override;
use App\Models\BackupDestination;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Livewire component for displaying and managing a single backup destination item.
 *
 * This component handles the display of a backup destination, its connection status,
 * and updates related to connection checks.
 */
class IndexItem extends Component
{
    public BackupDestination $backupDestination;

    /**
     * Handle the received BackupDestinationConnectionCheck event.
     *
     * This method checks the reachability of the backup destination and
     * displays an appropriate toast message.
     */
    public function echoReceivedEvent(): void
    {
        if ($this->backupDestination->isReachable()) {
            Toaster::success('The connection to the backup destination has been established.');
        } else {
            Toaster::error('The connection to the backup destination could not be established. Please check the credentials.');
        }

        Log::debug('Received the BackupDestinationConnectionCheck event. Refreshing the component.');
        $this->dispatch('$refresh');
    }

    /**
     * Update Livewire components related to this backup destination.
     *
     * This method refreshes the current component and dispatches an event
     * to update the CheckConnectionButton component.
     */
    public function updateLivewireComponents(): void
    {
        $this->dispatch('$refresh');

        // This will refresh the "CheckConnectionButton" component via its listener.
        $this->dispatch('update-backup-destination-check-button-' . $this->backupDestination->getAttribute('id'));
    }

    /**
     * Initialize the component with the given backup destination.
     */
    public function mount(BackupDestination $backupDestination): void
    {
        $this->backupDestination = $backupDestination;
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.backup-destinations.index-item');
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
            sprintf('echo-private:backup-destinations.%s,BackupDestinationConnectionCheck', $this->backupDestination->getAttribute('id')) => 'echoReceivedEvent',
            'backup-destination-connection-check-initiated-' . $this->backupDestination->getAttribute('id') => 'updateLivewireComponents',
        ];
    }
}
