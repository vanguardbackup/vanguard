<?php

declare(strict_types=1);

namespace App\Livewire\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Manages the display and interaction for a single remote server item.
 *
 * This component handles real-time updates and connectivity status changes
 * for an individual remote server in the index list.
 */
class IndexItem extends Component
{
    /** @var RemoteServer The remote server being displayed */
    public RemoteServer $remoteServer;

    /**
     * Handle the RemoteServerConnectivityStatusChanged event.
     *
     * Updates the UI based on the connectivity status and refreshes the component.
     */
    public function echoReceivedEvent(): void
    {
        if ($this->remoteServer->isOnline()) {
            Toaster::success('The connection to the remote server has been successfully established.');
        } else {
            Toaster::error('The connection to the remote server could not be established.');
        }

        Log::debug('Received the RemoteServerConnectivityStatusChanged event. Refreshing the component.');
        $this->dispatch('$refresh');
    }

    /**
     * Update Livewire components after a connection check is initiated.
     *
     * Refreshes this component and dispatches an event to update the CheckConnectionButton.
     */
    public function updateLivewireComponents(): void
    {
        $this->dispatch('$refresh');

        // This will refresh the "CheckConnectionButton" component via its listener.
        $this->dispatch('update-check-button-' . $this->remoteServer->getAttribute('id'));
    }

    /**
     * Initialize the component with a remote server.
     */
    public function mount(RemoteServer $remoteServer): void
    {
        $this->remoteServer = $remoteServer;
    }

    /**
     * Render the remote server index item.
     */
    public function render(): View
    {
        return view('livewire.remote-servers.index-item');
    }

    /**
     * Get the event listeners for the component.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:remote-servers.%s,RemoteServerConnectivityStatusChanged', $this->remoteServer->getAttribute('id')) => 'echoReceivedEvent',
            'connection-check-initiated-' . $this->remoteServer->getAttribute('id') => 'updateLivewireComponents',
        ];
    }
}
