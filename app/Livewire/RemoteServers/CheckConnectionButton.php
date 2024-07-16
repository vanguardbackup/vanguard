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

    public function refreshSelf(): void
    {
        $this->dispatch('$refresh');
    }

    public function checkConnection(): void
    {
        $this->remoteServer->runServerConnectionCheck();

        // This will call the "markAsChecking" method on the "IndexItem" component.
        $this->dispatch('connection-check-initiated-' . $this->remoteServer->getAttribute('id'));

        Toaster::info(__('Performing a connectivity check.'));
    }

    public function render(): View
    {
        return view('livewire.remote-servers.check-connection-button');
    }

    /**
     * Get the listeners array.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:remote-servers.%s,RemoteServerConnectivityStatusChanged', $this->remoteServer->getAttribute('id')) => 'refreshSelf',
            'update-check-button-' . $this->remoteServer->getAttribute('id') => '$refresh',
        ];
    }
}
