<?php

declare(strict_types=1);

namespace App\Actions\RemoteServer;

use App\Enums\ConnectionType;
use App\Events\RemoteServerConnectivityStatusChanged;
use App\Exceptions\ServerConnectionException;
use App\Factories\ServerConnectionFactory;
use App\Models\RemoteServer;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Manages remote server connection checks
 *
 * This class is responsible for verifying the connectivity status of remote servers.
 * It can perform checks using either a server ID or connection details provided directly.
 */
class CheckRemoteServerConnection
{
    private const int CONNECTION_TIMEOUT = 3;

    /**
     * @param  ServerConnectionFactory  $serverConnectionFactory  Factory for creating server connections
     */
    public function __construct(
        private readonly ServerConnectionFactory $serverConnectionFactory
    ) {}

    /**
     * Check connection status of a remote server by its ID
     *
     * Retrieves the server by ID and initiates a connection check.
     *
     * @param  int  $remoteServerId  The ID of the remote server to check
     * @return array<string, mixed> Connection check results
     *
     * @throws Exception If the server cannot be found or connection fails unexpectedly
     */
    public function byRemoteServerId(int $remoteServerId): array
    {
        $remoteServer = RemoteServer::findOrFail($remoteServerId);

        Log::debug('[Server Connection Check] Beginning connection check of server.', ['id' => $remoteServer->id]);

        return $this->checkServerConnection($remoteServer);
    }

    /**
     * Check connection status using provided server connection details
     *
     * Creates a temporary RemoteServer instance and initiates a connection check.
     *
     * @param  array<string, mixed>  $remoteServerConnectionDetails  Connection details including host, port, and username
     * @return array<string, mixed> Connection check results
     *
     * @throws Exception If required connection details are missing or connection fails unexpectedly
     */
    public function byRemoteServerConnectionDetails(array $remoteServerConnectionDetails): array
    {
        Log::debug('[Server Connection Check] Beginning connection check of server by connection details.', ['connection_details' => $remoteServerConnectionDetails]);

        if (! isset($remoteServerConnectionDetails['host'], $remoteServerConnectionDetails['port'], $remoteServerConnectionDetails['username'])) {
            throw new RuntimeException('Missing required data to check server connection. Ensure host, port, and username are provided.');
        }

        $remoteServer = new RemoteServer([
            'ip_address' => $remoteServerConnectionDetails['host'],
            'port' => $remoteServerConnectionDetails['port'],
            'username' => $remoteServerConnectionDetails['username'],
        ]);

        return $this->checkServerConnection($remoteServer);
    }

    /**
     * Perform the actual server connection check
     *
     * Attempts to establish an SSH connection to the server and updates its status accordingly.
     *
     * @param  RemoteServer  $remoteServer  The server to check
     * @return array<string, mixed> Connection check results
     *
     * @throws Exception If connection fails unexpectedly
     */
    private function checkServerConnection(RemoteServer $remoteServer): array
    {
        try {
            $connection = $this->serverConnectionFactory->makeFromModel($remoteServer, ConnectionType::SSH, self::CONNECTION_TIMEOUT);
            $connection->connect();

            $this->updateServerStatus($remoteServer, RemoteServer::STATUS_ONLINE);

            Log::debug('[Server Connection Check] Successfully connected to remote server');

            return [
                'connectivity_status' => RemoteServer::STATUS_ONLINE,
            ];

        } catch (ServerConnectionException $exception) {
            Log::info('[Server Connection Check] Unable to connect to remote server (offline)', [
                'error' => $exception->getMessage(),
                'server_id' => $remoteServer->getAttribute('id'),
            ]);

            $this->updateServerStatus($remoteServer, RemoteServer::STATUS_OFFLINE);

            return [
                'connectivity_status' => RemoteServer::STATUS_OFFLINE,
                'message' => 'Server is offline or unreachable',
            ];

        } finally {
            if (isset($connection)) {
                $connection->disconnect();
            }
        }
    }

    /**
     * Update the connectivity status of a remote server
     *
     * Updates the server's status in the database and dispatches a status change event.
     *
     * @param  RemoteServer  $remoteServer  The server to update
     * @param  string  $status  The new connectivity status
     */
    private function updateServerStatus(RemoteServer $remoteServer, string $status): void
    {
        if (! $remoteServer->exists()) {
            return;
        }

        $remoteServer->update([
            'connectivity_status' => $status,
        ]);

        Log::debug('[Server Connection Check] Dispatching RemoteServerConnectivityStatusChanged event (result: ' . $status . ')', ['remote_server' => $remoteServer]);
        RemoteServerConnectivityStatusChanged::dispatch($remoteServer, $status);
    }
}
