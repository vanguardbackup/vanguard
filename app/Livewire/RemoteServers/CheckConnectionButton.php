<?php

declare(strict_types=1);

namespace App\Livewire\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;
use Override;

/**
 * Manages the connection check button for remote servers.
 *
 * This component handles the UI and logic for initiating
 * connection checks to remote servers.
 */
class CheckConnectionButton extends Component
{
    /** @var RemoteServer The remote server to check */
    public RemoteServer $remoteServer;

    /**
     * Refresh the component.
     */
    public function refreshSelf(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Initiate a connection check for the remote server.
     *
     * Dispatches events and displays a notification.
     */
    public function checkConnection(): void
    {
        $this->remoteServer->runServerConnectionCheck();

        $this->dispatch('connection-check-initiated-' . $this->remoteServer->getAttribute('id'));

        Toaster::info('Performing a connectivity check.');
    }

    /**
     * Render the connection check button.
     */
    public function render(): View
    {
        return view('livewire.remote-servers.check-connection-button');
    }

    /**
     * Get the event listeners for the component.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:remote-servers.%s,RemoteServerConnectivityStatusChanged', $this->remoteServer->getAttribute('id')) => 'refreshSelf',
            'update-check-button-' . $this->remoteServer->getAttribute('id') => '$refresh',
        ];
    }
}
