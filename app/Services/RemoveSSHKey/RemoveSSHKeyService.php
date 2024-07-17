<?php

declare(strict_types=1);

namespace App\Services\RemoveSSHKey;

use App\Exceptions\SSHConnectionException;
use App\Models\RemoteServer;
use App\Services\RemoveSSHKey\Contracts\KeyRemovalNotifierInterface;
use App\Services\RemoveSSHKey\Contracts\SSHClientInterface;
use App\Services\RemoveSSHKey\Contracts\SSHKeyProviderInterface;
use Illuminate\Support\Facades\Log;

readonly class RemoveSSHKeyService
{
    public function __construct(
        private SSHClientInterface $sshClient,
        private KeyRemovalNotifierInterface $keyRemovalNotifier,
        private SSHKeyProviderInterface $sshKeyProvider
    ) {}

    /**
     * Handle the SSH key removal process for a given remote server.
     *
     * @param  RemoteServer  $remoteServer  The remote server to remove the SSH key from
     */
    public function handle(RemoteServer $remoteServer): void
    {
        Log::info('Initiating SSH key removal process.', ['server_id' => $remoteServer->getAttribute('id')]);

        try {
            $this->connectToServer($remoteServer);
            $this->removeKeyFromServer($remoteServer);
            $this->notifySuccess($remoteServer);
        } catch (SSHConnectionException $e) {
            $this->handleConnectionFailure($remoteServer, $e);
        }
    }

    /**
     * Establish an SSH connection to the remote server.
     *
     * @param  RemoteServer  $remoteServer  The remote server to connect to
     *
     * @throws SSHConnectionException If unable to connect to the remote server
     */
    private function connectToServer(RemoteServer $remoteServer): void
    {
        $privateKey = $this->sshKeyProvider->getPrivateKey();

        if (! $this->sshClient->connect(
            $remoteServer->getAttribute('ip_address'),
            $remoteServer->getAttribute('port'),
            $remoteServer->getAttribute('username'),
            $privateKey
        )) {
            throw new SSHConnectionException('Failed to connect to remote server');
        }
    }

    /**
     * Remove the SSH key from the remote server.
     *
     * @param  RemoteServer  $remoteServer  The remote server to remove the key from
     */
    private function removeKeyFromServer(RemoteServer $remoteServer): void
    {
        $publicKey = $this->sshKeyProvider->getPublicKey();
        $command = sprintf("sed -i -e '/^%s/d' ~/.ssh/authorized_keys", preg_quote($publicKey, '/'));

        $this->sshClient->executeCommand($command);

        Log::info('SSH key removed from server.', ['server_id' => $remoteServer->getAttribute('id')]);
    }

    /**
     * Notify the user of successful key removal.
     *
     * @param  RemoteServer  $remoteServer  The remote server the key was removed from
     */
    private function notifySuccess(RemoteServer $remoteServer): void
    {
        $this->keyRemovalNotifier->notifySuccess($remoteServer);
        Log::info('User notified of successful key removal.', ['server_id' => $remoteServer->getAttribute('id')]);
    }

    /**
     * Handle and log connection failures, and notify the user.
     *
     * @param  RemoteServer  $remoteServer  The remote server that failed to connect
     * @param  SSHConnectionException  $sshConnectionException  The exception that occurred
     */
    private function handleConnectionFailure(RemoteServer $remoteServer, SSHConnectionException $sshConnectionException): void
    {
        Log::error('Failed to connect to remote server for key removal.', [
            'server_id' => $remoteServer->getAttribute('id'),
            'error' => $sshConnectionException->getMessage(),
        ]);

        $this->keyRemovalNotifier->notifyFailure($remoteServer, $sshConnectionException->getMessage());
    }
}
