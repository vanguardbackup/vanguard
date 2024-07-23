<?php

declare(strict_types=1);

namespace App\Services\RemoveSSHKey;

use App\Facades\ServerConnection;
use App\Mail\RemoteServers\FailedToRemoveKey;
use App\Mail\RemoteServers\SuccessfullyRemovedKey;
use App\Models\RemoteServer;
use App\Support\ServerConnection\Connection;
use App\Support\ServerConnection\Exceptions\ConnectionException;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;

class RemoveSSHKeyService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle the SSH key removal process for a given remote server.
     *
     * This method orchestrates the process of removing an SSH key from a remote server,
     * including establishing a connection, executing the removal command, and notifying
     * the user of the result.
     *
     * @param  RemoteServer  $remoteServer  The remote server to remove the SSH key from
     */
    public function handle(RemoteServer $remoteServer): void
    {
        $this->logger->info("Initiating SSH key removal for server: {$remoteServer->label}");

        try {
            $connection = $this->establishConnection($remoteServer);
            $publicKey = $this->getSSHPublicKey();

            if (! $this->determineKeyExistence($connection, $publicKey)) {
                $this->logger->info("SSH key does not exist on server: {$remoteServer->label}");
                $this->handleSuccessfulRemoval($remoteServer);

                return;
            }

            $result = $this->executeRemovalCommand($connection, $publicKey);
            $this->processRemovalResult($remoteServer, $result);

        } catch (ConnectionException $e) {
            $this->handleConnectionFailure($remoteServer, $e);
        }
    }

    /**
     * Establish a connection to the remote server.
     *
     * @return Connection The connection instance.
     *
     * @throws ConnectionException
     */
    private function establishConnection(RemoteServer $remoteServer): Connection
    {
        $this->logger->debug("Attempting to connect to server: {$remoteServer->label}");

        return ServerConnection::connectFromModel($remoteServer)->establish();
    }

    /**
     * Get the SSH public key.
     */
    private function getSSHPublicKey(): string
    {
        //TODO: Replace with a ServerConnectionManager readonly implementation that gets the public key
        return get_ssh_public_key();
    }

    /**
     * Check if the SSH public key exists on the remote server.
     */
    private function determineKeyExistence(Connection $connection, string $publicKey): bool
    {
        $command = sprintf("grep -q '%s' ~/.ssh/authorized_keys", preg_quote($publicKey, '/'));
        $this->logger->debug("Checking if SSH key exists with command: {$command}");

        $result = $connection->run($command);

        return $result === '';
    }

    /**
     * Execute the SSH key removal command on the remote server.
     *
     * @return string The result of the command execution
     */
    private function executeRemovalCommand(Connection $connection, string $publicKey): string
    {
        $command = sprintf("sed -i -e '/^%s/d' ~/.ssh/authorized_keys", preg_quote($publicKey, '/'));
        $this->logger->debug("Executing removal command: {$command}");

        return $connection->run($command);
    }

    /**
     * Process the result of the key removal attempt.
     */
    private function processRemovalResult(RemoteServer $remoteServer, string $result): void
    {
        if ($this->isRemovalSuccessful($result)) {
            $this->handleSuccessfulRemoval($remoteServer);
        } else {
            $this->handleFailedRemoval($remoteServer, $result);
        }
    }

    /**
     * Determine if the key removal was successful based on the command result.
     */
    private function isRemovalSuccessful(string $result): bool
    {
        return $result === '' || str_contains($result, 'Successfully removed');
    }

    /**
     * Handle successful key removal.
     */
    private function handleSuccessfulRemoval(RemoteServer $remoteServer): void
    {
        $this->logger->info("Successfully removed SSH key from server: {$remoteServer->label}");

        Mail::to($remoteServer->user)->queue(new SuccessfullyRemovedKey($remoteServer));
    }

    /**
     * Handle failed key removal.
     */
    private function handleFailedRemoval(RemoteServer $remoteServer, string $result): void
    {
        $this->logger->error("Failed to remove SSH key from server: {$remoteServer->label}. Result: {$result}");

        Mail::to($remoteServer->user)->queue(new FailedToRemoveKey($remoteServer));
    }

    /**
     * Handle connection failure.
     */
    private function handleConnectionFailure(RemoteServer $remoteServer, ConnectionException $exception): void
    {
        $this->logger->error("Failed to connect to server: {$remoteServer->label}. Error: {$exception->getMessage()}");

        Mail::to($remoteServer->user)->queue(new FailedToRemoveKey($remoteServer));
    }
}
