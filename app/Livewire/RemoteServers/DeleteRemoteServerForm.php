<?php

declare(strict_types=1);

namespace App\Livewire\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

/**
 * Manages the form for deleting a remote server.
 *
 * This component handles the UI and logic for removing a remote server,
 * including authorization and the deletion process.
 */
class DeleteRemoteServerForm extends Component
{
    /** @var RemoteServer The remote server to be deleted */
    public RemoteServer $remoteServer;

    /**
     * Render the delete remote server form.
     */
    public function render(): View
    {
        return view('livewire.remote-servers.delete-remote-server-form');
    }

    /**
     * Initialize the component with a remote server.
     */
    public function mount(RemoteServer $remoteServer): void
    {
        $this->remoteServer = $remoteServer;
    }

    /**
     * Delete the remote server.
     *
     * Authorizes the action, initiates the server removal process,
     * and redirects to the remote servers index page.
     */
    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->remoteServer);

        $this->remoteServer->removeServer();

        Toaster::success('Remote server will be removed shortly.');

        return Redirect::route('remote-servers.index');
    }
}
