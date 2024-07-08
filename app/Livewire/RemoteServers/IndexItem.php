<?php

declare(strict_types=1);

namespace App\Livewire\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class IndexItem extends Component
{
    public RemoteServer $remoteServer;

    /**
     * Get the listeners array.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            "echo-private:remote-servers.{$this->remoteServer->getAttribute('id')},RemoteServerConnectivityStatusChanged" => 'echoReceivedEvent',
            "connection-check-initiated-{$this->remoteServer->getAttribute('id')}" => 'updateLivewireComponents',
        ];
    }

    public function echoReceivedEvent(): void
    {
        if ($this->remoteServer->isOnline()) {
            Toaster::success(__('The connection to the remote server has been successfully established.'));
        } else {
            Toaster::error(__('The connection to the remote server could not be established.'));
        }

        Log::debug('Received the RemoteServerConnectivityStatusChanged event. Refreshing the component.');
        $this->dispatch('$refresh');
    }

    public function updateLivewireComponents(): void
    {
        $this->dispatch('$refresh');

        // This will refresh the "CheckConnectionButton" component via its listener.
        $this->dispatch('update-check-button-' . $this->remoteServer->getAttribute('id'));
    }

    public function mount(RemoteServer $remoteServer): void
    {
        $this->remoteServer = $remoteServer;
    }

    public function render(): View
    {
        return view('livewire.remote-servers.index-item');
    }
}
