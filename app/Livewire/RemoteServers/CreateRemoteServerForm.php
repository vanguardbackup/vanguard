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

class CreateRemoteServerForm extends Component
{
    public bool $canConnectToRemoteServer = false;

    public bool $showingConnectionView = false;

    public bool $testOverride = false;  // This is a test override to allow the test to bypass the connection check

    public string $label = '';

    public string $host = '';

    public string $username = '';

    public int $port = 22;

    public string $connectionError = '';

    public string $ourPublicKey = '';

    public ?string $databasePassword = '';

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

        Toaster::success(__('Remote server has been added.'));

        $this->dispatch('serverAdded');
    }

    public function render(): View
    {
        if (ssh_keys_exist()) {
            $key = get_ssh_public_key();
            $this->ourPublicKey = sprintf("mkdir -p ~/.ssh && echo '%s' >> ~/.ssh/authorized_keys", $key);
        }

        return view('livewire.remote-servers.create-remote-server-form');
    }

    public function returnToForm(): void
    {
        $this->showingConnectionView = false;
    }

    public function usingServerProvider(string $provider): void
    {
        Toaster::success(__('The username has been updated to ":username".', ['username' => $provider]));

        $this->username = $provider;
        $this->port = 22;
    }

    /**
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

        if ($response['status'] === 'error') {
            $this->connectionError = $response['error'];
        }

        return $response['status'] === 'success';
    }
}
