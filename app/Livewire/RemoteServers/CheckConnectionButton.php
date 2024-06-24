<?php

declare(strict_types=1);

namespace App\Livewire\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class CheckConnectionButton extends Component
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
            "echo:remote-servers.{$this->remoteServer->id},RemoteServerConnectivityStatusChanged" => 'refreshSelf',
            "update-check-button-{$this->remoteServer->id}" => '$refresh',
        ];
    }

    public function refreshSelf(): void
    {
        $this->dispatch('$refresh');
    }

    public function checkConnection(): void
    {
        $this->remoteServer->runServerConnectionCheck();

        // This will call the "markAsChecking" method on the "IndexItem" component.
        $this->dispatch('connection-check-initiated-' . $this->remoteServer->id);

        Toaster::info(__('Performing a connectivity check.'));
    }

    public function render(): View
    {
        return view('livewire.remote-servers.check-connection-button');
    }
}
