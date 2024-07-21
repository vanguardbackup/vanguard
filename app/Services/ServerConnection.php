<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ServerConnectionInterface;
use App\Enums\ConnectionType;
use App\Exceptions\ServerConnectionException;
use App\Models\RemoteServer;
use Exception;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

/**
 * ServerConnection class for managing connections to remote servers.
 *
 * This class provides functionality to connect to remote servers using SSH or SFTP,
 * execute commands, upload and download files, and manage the connection lifecycle.
 * It implements the ServerConnectionInterface for consistent use across the application.
 */
class ServerConnection implements ServerConnectionInterface
{
    private SSH2|SFTP|null $ssh2 = null;
    private ?string $privateKeyPath;

    /**
     * ServerConnection constructor.
     *
     * @param  RemoteServer  $remoteServer  The remote server to connect to
     * @param  ConnectionType  $connectionType  The type of connection (SSH or SFTP)
     * @param  string|null  $privateKeyPath  Optional custom path to the private key file
     *
     * @throws ServerConnectionException
     */
    public function __construct(private readonly RemoteServer $remoteServer, private readonly ConnectionType $connectionType, ?string $privateKeyPath = null)
    {
        $this->privateKeyPath = $privateKeyPath ?? $this->getDefaultPrivateKeyPath();
    }

    /**
     * Establish a connection to the remote server.
     *
     * @throws ServerConnectionException If connection fails or private key cannot be loaded
     */
    public function connect(): void
    {
        $this->ssh2 = match ($this->connectionType) {
            ConnectionType::SSH => new SSH2($this->remoteServer->getAttribute('ip_address'), (int) $this->remoteServer->getAttribute('port')),
            ConnectionType::SFTP => new SFTP($this->remoteServer->getAttribute('ip_address'), (int) $this->remoteServer->getAttribute('port')),
        };

        $keyContent = file_get_contents((string) $this->privateKeyPath);
        if ($keyContent === false) {
            throw new ServerConnectionException('Failed to read private key file.');
        }

        try {
            /** @var PrivateKey $key */
            $key = PublicKeyLoader::load($keyContent);
        } catch (Exception $e) {
            throw new ServerConnectionException("Failed to load private key: {$e->getMessage()}");
        }

        if (! $this->ssh2->login($this->remoteServer->getAttribute('username'), $key)) {
            throw new ServerConnectionException("Failed to connect to server: {$this->remoteServer->getAttribute('ip_address')}");
        }
    }

    /**
     * Disconnect from the remote server.
     */
    public function disconnect(): void
    {
        if ($this->ssh2 instanceof SSH2) {
            $this->ssh2->disconnect();
            $this->ssh2 = null;
        }
    }

    /**
     * Execute a command on the remote server.
     *
     * @param  string  $command  The command to execute
     * @return string The output of the command
     *
     * @throws ServerConnectionException If not connected or if the command execution fails
     */
    public function executeCommand(string $command): string
    {
        if (! $this->ssh2 instanceof SSH2) {
            throw new ServerConnectionException('Not connected to server. Call connect() first.');
        }

        $output = $this->ssh2->exec($command);

        if ($output === false) {
            throw new ServerConnectionException("Failed to execute command: {$command}");
        }

        return (string) $output;
    }

    /**
     * Upload a file to the remote server using SFTP.
     *
     * @param  string  $localPath  The local path of the file to upload
     * @param  string  $remotePath  The remote path where the file should be uploaded
     *
     * @throws ServerConnectionException If not connected via SFTP or if the upload fails
     */
    public function uploadFile(string $localPath, string $remotePath): void
    {
        if (! $this->ssh2 instanceof SFTP) {
            throw new ServerConnectionException('File upload is only supported for SFTP connections');
        }

        if (! $this->ssh2->put($remotePath, $localPath, SFTP::SOURCE_LOCAL_FILE)) {
            throw new ServerConnectionException("Failed to upload file: {$localPath}");
        }
    }

    /**
     * Download a file from the remote server using SFTP.
     *
     * @param  string  $remotePath  The remote path of the file to download
     * @param  string  $localPath  The local path where the file should be saved
     *
     * @throws ServerConnectionException If not connected via SFTP or if the download fails
     */
    public function downloadFile(string $remotePath, string $localPath): void
    {
        if (! $this->ssh2 instanceof SFTP) {
            throw new ServerConnectionException('File download is only supported for SFTP connections');
        }

        if (! $this->ssh2->get($remotePath, $localPath)) {
            throw new ServerConnectionException("Failed to download file: {$remotePath}");
        }
    }

    /**
     * Set a custom private key path for the connection.
     *
     * @param  string  $path  The path to the private key file
     */
    public function setPrivateKeyPath(string $path): self
    {
        $this->privateKeyPath = $path;

        return $this;
    }

    /**
     * Get the current connection type.
     *
     * @return ConnectionType The current connection type (SSH or SFTP)
     */
    public function getConnectionType(): ConnectionType
    {
        return $this->connectionType;
    }

    /**
     * Get the default private key path.
     *
     * @return string The path to the default private key file
     *
     * @throws ServerConnectionException If the default private key file is not found
     */
    private function getDefaultPrivateKeyPath(): string
    {
        $path = Storage::disk('local')->path('ssh/key');

        if (! file_exists($path)) {
            throw new ServerConnectionException('Default SSH private key not found in storage/app/ssh/key');
        }

        return $path;
    }
}
