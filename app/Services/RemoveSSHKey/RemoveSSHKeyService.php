<?php

declare(strict_types=1);

namespace App\Services\RemoveSSHKey;

use App\Facades\ServerConnection;
use App\Mail\RemoteServers\FailedToRemoveKey;
use App\Mail\RemoteServers\SuccessfullyRemovedKey;
use App\Models\RemoteServer;
use App\Support\ServerConnection\Connection;
use App\Support\ServerConnection\Exceptions\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles the process of removing SSH keys from remote servers.
 */
class RemoveSSHKeyService
{
    /**
     * Orchestrates the SSH key removal process for a given remote server.
     *
     * Establishes a connection, executes the removal command, and notifies the user of the result.
     */
    public function handle(RemoteServer $remoteServer): void
    {
        Log::info("Initiating SSH key removal for server: {$remoteServer->getAttribute('label')}");

        try {
            $connection = $this->establishConnection($remoteServer);
            $publicKey = $this->getSSHPublicKey();

            if (! $this->isKeyPresent($connection, $publicKey)) {
                Log::info("SSH key not present on server: {$remoteServer->getAttribute('label')}");
                $this->notifySuccess($remoteServer);

                return;
            }

            $result = $this->removeKey($connection, $publicKey);

            $this->processResult($remoteServer, $result);

        } catch (ConnectionException $e) {
            $this->handleConnectionFailure($remoteServer, $e);
        }
    }

    /**
     * Establishes a connection to the remote server.
     *
     * @throws ConnectionException
     */
    private function establishConnection(RemoteServer $remoteServer): Connection
    {
        Log::debug("Connecting to server: {$remoteServer->getAttribute('label')}");

        return ServerConnection::connectFromModel($remoteServer)->establish();
    }

    /**
     * Retrieves the SSH public key content.
     */
    private function getSSHPublicKey(): string
    {
        return ServerConnection::getDefaultPublicKey();
    }

    /**
     * Checks if the SSH public key exists on the remote server.
     */
    private function isKeyPresent(Connection $connection, string $publicKey): bool
    {
        $escapedKey = preg_quote(trim($publicKey), '/');
        $command = sprintf("grep -q '%s' ~/.ssh/authorized_keys", $escapedKey);
        Log::debug("Checking SSH key presence: {$command}");

        return $connection->run($command) === '';
    }

    /**
     * Executes the SSH key removal command on the remote server.
     */
    private function removeKey(Connection $connection, string $publicKey): string
    {
        $escapedKey = preg_quote(trim($publicKey), '/');
        $command = sprintf("sed -i -e '/^%s/d' ~/.ssh/authorized_keys", $escapedKey);
        Log::debug("Executing key removal: {$command}");

        return $connection->run($command);
    }

    /**
     * Processes the result of the key removal attempt.
     */
    private function processResult(RemoteServer $remoteServer, string $result): void
    {
        if ($result === '' || str_contains($result, 'Successfully removed')) {
            $this->notifySuccess($remoteServer);

            return;
        }

        $this->notifyFailure($remoteServer, $result);
    }

    /**
     * Handles successful key removal notification.
     */
    private function notifySuccess(RemoteServer $remoteServer): void
    {
        Log::info("SSH key removed from server: {$remoteServer->getAttribute('label')}");
        Mail::to($remoteServer->getAttribute('user'))->queue(new SuccessfullyRemovedKey($remoteServer));
    }

    /**
     * Handles failed key removal notification.
     */
    private function notifyFailure(RemoteServer $remoteServer, string $result): void
    {
        Log::error("Failed to remove SSH key from server: {$remoteServer->getAttribute('label')}. Result: {$result}");
        Mail::to($remoteServer->getAttribute('user'))->queue(new FailedToRemoveKey($remoteServer));
    }

    /**
     * Handles connection failure notification.
     */
    private function handleConnectionFailure(RemoteServer $remoteServer, ConnectionException $connectionException): void
    {
        Log::error("Failed to connect to server: {$remoteServer->getAttribute('label')}. Error: {$connectionException->getMessage()}");
        Mail::to($remoteServer->getAttribute('user'))->queue(new FailedToRemoveKey($remoteServer));
    }
}
