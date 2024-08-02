<?php

declare(strict_types=1);

namespace App\Livewire\RemoteServers;

use App\Actions\RemoteServer\CheckRemoteServerConnection;
use App\Models\RemoteServer;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Livewire\Component;
use Toaster;

/**
 * Manages the form for creating a new remote server.
 *
 * This component handles the UI and logic for adding a new remote server,
 * including connection testing and data validation.
 */
class CreateRemoteServerForm extends Component
{
    /** @var bool Indicates if a connection to the remote server can be established */
    public bool $canConnectToRemoteServer = false;

    /** @var bool Controls the visibility of the connection view */
    public bool $showingConnectionView = false;

    /** @var bool Test override flag for bypassing connection checks in tests */
    public bool $testOverride = false;

    /** @var string Label for the remote server */
    public string $label = '';

    /** @var string Host address of the remote server */
    public string $host = '';

    /** @var string Username for the remote server connection */
    public string $username = '';

    /** @var int Port number for the remote server connection */
    public int $port = 22;

    /** @var string Stores any connection error messages */
    public string $connectionError = '';

    /** @var string Public key for SSH connection */
    public string $ourPublicKey = '';

    /** @var string|null Optional database password for the remote server */
    public ?string $databasePassword = '';

    /**
     * Handle form submission.
     *
     * Validates input, tests connection, and creates a new RemoteServer if successful.
     */
    public function submit(): void
    {
        $this->validate([
            'label' => ['required', 'string'],
            'host' => ['required', 'string', 'unique:remote_servers,ip_address', 'ip'],
            'username' => ['required', 'string'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'databasePassword' => ['string', 'nullable'],
        ], [
            'host.required' => __('Please enter the IP address of your remote server.'),
            'host.unique' => __('This remote server has already been added.'),
            'host.ip' => __('The IP address must be a valid IP address.'),
            'label.required' => __('Please enter a label for this remote server.'),
            'username.required' => __('Please enter a username for this remote server.'),
            'port.integer' => __('Please enter a valid port number.'),
            'port.min' => __('The port number you have entered is invalid.'),
            'port.max' => __('The port number you have entered is invalid.'),
        ]);

        $this->showingConnectionView = true;

        if ($this->testOverride && $this->connectionAttempt() === false) {
            $this->canConnectToRemoteServer = true;
        }

        if (! $this->connectionAttempt() && $this->testOverride === false) {
            $this->canConnectToRemoteServer = false;

            return;
        }

        $this->canConnectToRemoteServer = true;

        $remoteServer = RemoteServer::create([
            'label' => $this->label,
            'ip_address' => $this->host,
            'username' => $this->username,
            'port' => $this->port,
            'user_id' => Auth::id(),
            'connectivity_status' => RemoteServer::STATUS_ONLINE,
            'database_password' => $this->databasePassword ? Crypt::encryptString($this->databasePassword) : null,
        ]);

        $remoteServer->updateLastConnectedAt();

        Toaster::success('Remote server has been added.');

        $this->dispatch('serverAdded');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        if (ssh_keys_exist()) {
            $key = get_ssh_public_key();
            $this->ourPublicKey = sprintf("mkdir -p ~/.ssh && echo '%s' >> ~/.ssh/authorized_keys", $key);
        }

        return view('livewire.remote-servers.create-remote-server-form');
    }

    /**
     * Return to the form view from the connection view.
     */
    public function returnToForm(): void
    {
        $this->showingConnectionView = false;
    }

    /**
     * Set the username based on the selected server provider.
     */
    public function usingServerProvider(string $provider): void
    {
        Toaster::success('The username has been updated to ":username".', ['username' => $provider]);

        $this->username = $provider;
        $this->port = 22;
    }

    /**
     * Attempt to connect to the remote server.
     *
     * @throws Exception
     */
    private function connectionAttempt(): bool
    {
        $checkRemoteServerConnection = new CheckRemoteServerConnection;

        $response = $checkRemoteServerConnection->byRemoteServerConnectionDetails([
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
        ]);

        $status = $response['status'] ?? 'error';
        $error = $response['error'] ?? $response['message'] ?? 'Unknown error occurred';

        if ($status === 'error') {
            $this->connectionError = $error;

            return false;
        }

        return true;
    }
}
