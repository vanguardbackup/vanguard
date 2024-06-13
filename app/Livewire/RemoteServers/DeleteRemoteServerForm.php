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

        $this->remoteServer->delete();

        Toaster::success('Remote server has been removed.');

        return Redirect::route('remote-servers.index');
    }
}
