<?php

declare(strict_types=1);

namespace App\Livewire\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Toaster;

/**
 * Manages the form for updating an existing remote server.
 *
 * This component handles the UI and logic for modifying remote server details,
 * including validation, update process, and optional database password encryption.
 */
class UpdateRemoteServerForm extends Component
{
    /** @var RemoteServer The remote server being updated */
    public RemoteServer $remoteServer;

    /** @var string Label for the remote server */
    public string $label = '';

    /** @var string Host address of the remote server */
    public string $host = '';

    /** @var string Username for the remote server connection */
    public string $username = '';

    /** @var int Port number for the remote server connection */
    public int $port = 22;

    /** @var string|null Optional database password for the remote server */
    public ?string $databasePassword = '';

    /**
     * Initialize the component with existing remote server data.
     */
    public function mount(): void
    {
        $this->label = $this->remoteServer->getAttribute('label');
        $this->host = $this->remoteServer->getAttribute('ip_address');
        $this->username = $this->remoteServer->getAttribute('username');
        $this->port = (int) $this->remoteServer->getAttribute('port');
        $this->databasePassword = '';
    }

    /**
     * Submit the form and update the remote server.
     *
     * Validates input, updates the remote server, and optionally encrypts the database password.
     */
    public function submit(): RedirectResponse|Redirector
    {
        $this->authorize('update', $this->remoteServer);

        $this->validate([
            'label' => ['required', 'string'],
            'host' => ['required', 'string', 'ip', 'unique:remote_servers,ip_address,' . $this->remoteServer->getAttribute('id')],
            'username' => ['required', 'string'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'databasePassword' => ['string', 'nullable'],
        ], [
            'host.unique' => __('This remote server has already been added.'),
            'host.ip' => __('The IP address must be a valid IP address.'),
            'label.required' => __('Please enter a label for this remote server.'),
            'username.required' => __('Please enter a username for this remote server.'),
            'port.integer' => __('Please enter a valid port number.'),
            'port.min' => __('The port number you have entered is invalid.'),
            'port.max' => __('The port number you have entered is invalid.'),
        ]);

        $this->remoteServer->update([
            'label' => $this->label,
            'ip_address' => $this->host,
            'username' => $this->username,
            'port' => $this->port,
        ]);

        if ($this->databasePassword) {
            $this->remoteServer->updateQuietly([
                'database_password' => Crypt::encryptString($this->databasePassword),
            ]);
        }

        $this->remoteServer->save();

        Toaster::success('Remote server details saved.');

        return Redirect::route('remote-servers.index');
    }

    /**
     * Render the update remote server form.
     */
    public function render(): View
    {
        return view('livewire.remote-servers.update-remote-server-form');
    }
}
