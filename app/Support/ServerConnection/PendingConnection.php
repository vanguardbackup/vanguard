<?php

declare(strict_types=1);

namespace App\Support\ServerConnection;

use App\Models\RemoteServer;
use App\Support\ServerConnection\Exceptions\ConnectionException;
use Exception;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use RuntimeException;

/**
 * Represents a pending connection to a remote server.
 *
 * This class is responsible for configuring and establishing
 * a connection to a remote server using SSH or SFTP.
 */
class PendingConnection
{
    /** @var SFTP|SSH2|null The connection object */
    protected SSH2|SFTP|null $connection = null;

    /** @var string|null The hostname or IP address of the remote server */
    protected ?string $host = null;

    /** @var int The port number for the connection */
    protected int $port = 22;

    /** @var string|null The username for authentication */
    protected ?string $username = null;

    /** @var string|null The path to the private key file for authentication */
    protected ?string $privateKey = null;

    /** @var string|null The passphrase for the private key */
    protected ?string $passphrase = null;

    /** @var int The connection timeout in seconds */
    protected int $timeout = 30;

    /** @var bool Flag to indicate if default credentials should be used */
    protected bool $useDefaultCredentials = true;

    /**
     * Create a new PendingConnection instance.
     *
     * Initializes the connection with default private key and passphrase
     * from the ServerConnectionManager.
     */
    public function __construct()
    {
        $this->privateKey = ServerConnectionManager::getDefaultPrivateKey();
        $this->passphrase = ServerConnectionManager::getDefaultPassphrase();
    }

    /**
     * Set the connection timeout.
     *
     * @param  int  $seconds  The timeout in seconds
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Set the connection details from a RemoteServer model.
     *
     * @param  RemoteServer  $remoteServer  The RemoteServer model instance
     */
    public function connectFromModel(RemoteServer $remoteServer): self
    {
        $this->host = $remoteServer->getAttribute('ip_address');
        $this->port = (int) $remoteServer->getAttribute('port');
        $this->username = $remoteServer->getAttribute('username');

        return $this;
    }

    /**
     * Set the connection details manually.
     *
     * @param  string  $host  The hostname or IP address
     * @param  int  $port  The port number
     * @param  string  $username  The username
     */
    public function connect(string $host, int $port = 22, string $username = 'root'): self
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;

        return $this;
    }

    /**
     * Set the private key for authentication.
     *
     * @param  string|null  $privateKeyPath  The path to the private key file
     * @param  string|null  $passphrase  The passphrase for the private key
     */
    public function withPrivateKey(?string $privateKeyPath = null, ?string $passphrase = null): self
    {
        $this->privateKey = $privateKeyPath ?? $this->privateKey;
        $this->passphrase = $passphrase ?? $this->passphrase;
        $this->useDefaultCredentials = false;

        return $this;
    }

    /**
     * Establish the connection.
     *
     * @throws ConnectionException If unable to connect or authenticate
     */
    public function establish(): Connection
    {
        $this->validateConnectionDetails();

        try {
            $this->createConnection();
            $this->authenticateConnection();

            if (! $this->connection instanceof SSH2 || ! $this->connection->isConnected() || ! $this->connection->isAuthenticated()) {
                throw new RuntimeException('Connection not fully established and authenticated');
            }

            Log::info('Successfully connected to the remote server.', [
                'host' => $this->host,
                'port' => $this->port,
            ]);

            return new Connection($this->connection);
        } catch (Exception $e) {
            Log::error('Failed to establish connection', [
                'error' => $e->getMessage(),
                'host' => $this->host,
            ]);
            throw ConnectionException::withMessage('Unable to connect to the server: ' . $e->getMessage());
        }
    }

    /**
     * Validate that all necessary connection details are provided.
     *
     * @throws ConnectionException If connection details are insufficient
     */
    protected function validateConnectionDetails(): void
    {
        $missingDetails = array_filter([
            'host' => $this->host,
            'username' => $this->username,
        ], fn ($value): bool => $value === null);

        if ($missingDetails !== []) {
            $missingFields = implode(', ', array_keys($missingDetails));
            throw ConnectionException::withMessage("Insufficient connection details provided. Missing: {$missingFields}");
        }
    }

    /**
     * Create the underlying connection object.
     *
     * @throws RuntimeException If connection creation fails
     */
    protected function createConnection(): void
    {
        try {
            $this->connection = new SSH2($this->host, $this->port, $this->timeout);
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create SSH connection: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Authenticate the connection.
     *
     * @throws ConnectionException If authentication fails
     */
    protected function authenticateConnection(): void
    {
        if (! $this->connection instanceof SSH2) {
            throw ConnectionException::withMessage('The connection has not been established or has become invalid.');
        }

        $privateKey = $this->loadPrivateKey();

        if (! $this->connection->login((string) $this->username, $privateKey)) {
            throw ConnectionException::authenticationFailed();
        }
    }

    /**
     * Load the private key.
     *
     * @throws ConnectionException If unable to load the private key
     */
    protected function loadPrivateKey(): PrivateKey
    {
        $passphrase = (string) $this->passphrase;

        try {
            $keyContent = $this->readPrivateKeyFile();
            $privateKey = PublicKeyLoader::load($keyContent, $passphrase);

            if (! $privateKey instanceof PrivateKey) {
                throw new RuntimeException('Invalid private key format.');
            }

            return $privateKey;
        } catch (Exception $e) {
            throw ConnectionException::withMessage('Failed to load private key: ' . $e->getMessage());
        }
    }

    /**
     * Read the private key file.
     *
     * @throws ConnectionException If unable to read the private key file
     */
    protected function readPrivateKeyFile(): string
    {
        $keyPath = (string) $this->privateKey;

        if (! str_starts_with($keyPath, '/')) {
            $keyPath = storage_path('app/' . $keyPath);
        }

        if (! file_exists($keyPath)) {
            throw ConnectionException::withMessage("Private key file does not exist: {$keyPath}");
        }

        $keyContent = file_get_contents($keyPath);

        if ($keyContent === false) {
            throw ConnectionException::withMessage("Unable to read private key file: {$keyPath}");
        }

        return $keyContent;
    }
}
