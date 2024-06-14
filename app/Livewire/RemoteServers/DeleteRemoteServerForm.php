<?php

namespace App\Livewire\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

class DeleteRemoteServerForm extends Component
{
    public RemoteServer $remoteServer;

    public function render(): View
    {
        return view('livewire.remote-servers.delete-remote-server-form');
    }

    public function mount(RemoteServer $remoteServer): void
    {
        $this->remoteServer = $remoteServer;
    }

    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->remoteServer);

        $this->remoteServer->removeServer();

        Toaster::success('Remote server will be removed shortly.');

        return Redirect::route('remote-servers.index');
    }
}
