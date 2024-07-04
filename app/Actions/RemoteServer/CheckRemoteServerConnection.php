<?php

declare(strict_types=1);

namespace App\Actions\RemoteServer;

use App\Events\RemoteServerConnectivityStatusChanged;
use App\Models\RemoteServer;
use Exception;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use RuntimeException;

class CheckRemoteServerConnection
{
    /**
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function byRemoteServerId(int $remoteServerId): array
    {
        $remoteServer = RemoteServer::findOrFail($remoteServerId);

        Log::debug('[Server Connection Check] Beginning connection check of server.', ['id' => $remoteServer->id]);

        return $this->checkServerConnection([
            'host' => $remoteServer->ip_address,
            'port' => $remoteServer->port,
            'username' => $remoteServer->username,
        ], $remoteServer);
    }

    /**
     * @param  array<string, mixed>  $remoteServerConnectionDetails
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function byRemoteServerConnectionDetails(array $remoteServerConnectionDetails): array
    {
        Log::debug('[Server Connection Check] Beginning connection check of server by connection details.', ['connection_details' => $remoteServerConnectionDetails]);

        return $this->checkServerConnection($remoteServerConnectionDetails);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    private function checkServerConnection(array $data, ?RemoteServer $remoteServer = null): array
    {
        if (! array_key_exists('host', $data) || ! array_key_exists('port', $data) || ! array_key_exists('username', $data)) {
            throw new RuntimeException('Missing required data to check server connection. Ensure host, port, and username are provided.');
        }

        try {
            /** @var PrivateKey $key */
            $key = PublicKeyLoader::load(get_ssh_private_key(), config('app.ssh.passphrase'));

            $ssh = new SSH2($data['host'], $data['port']);

            // Attempt to login
            if (! $ssh->login($data['username'], $key)) {
                Log::debug('[Server Connection Check] Failed to connect to remote server', ['error' => $ssh->getLastError()]);

                $remoteServer?->update([
                    'connectivity_status' => RemoteServer::STATUS_OFFLINE,
                ]);

                return [
                    'error' => $ssh->getLastError(),
                    'status' => 'error',
                ];
            }

            $remoteServer?->update([
                'connectivity_status' => RemoteServer::STATUS_ONLINE,
            ]);

            Log::debug('[Server Connection Check] Successfully connected to remote server');

            if ($remoteServer instanceof RemoteServer) {
                Log::debug('[Server Connection Check] Dispatching RemoteServerConnectivityStatusChanged event (result: ' . $remoteServer->connectivity_status . ')', ['remote_server' => $remoteServer]);
                RemoteServerConnectivityStatusChanged::dispatch($remoteServer, $remoteServer->connectivity_status);
            }

            return [
                'status' => 'success',
            ];

        } catch (Exception $exception) {
            Log::error('[Server Connection Check] Failed to connect to remote server', ['error' => $exception->getMessage()]);

            $remoteServer?->update([
                'connectivity_status' => RemoteServer::STATUS_OFFLINE,
            ]);

            if ($remoteServer instanceof RemoteServer) {
                Log::debug('[Server Connection Check] Dispatching RemoteServerConnectivityStatusChanged event (result: ' . $remoteServer->connectivity_status . ')', ['remote_server' => $remoteServer]);
                RemoteServerConnectivityStatusChanged::dispatch($remoteServer, $remoteServer->connectivity_status);
            }

            return [
                'error' => $exception->getMessage(),
                'status' => 'error',
            ];
        }
    }
}
