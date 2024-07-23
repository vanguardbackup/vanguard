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
    protected int $timeout = 10;

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
        $this->passphrase = ServerConnectionManager::getDefaultPassphrase();

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
     * @param  string  $privateKeyPath  The path to the private key file
     * @param  string|null  $passphrase  The passphrase for the private key
     */
    public function withPrivateKey(string $privateKeyPath, ?string $passphrase = null): self
    {
        $this->privateKey = $privateKeyPath;
        $this->passphrase = $passphrase ?? ServerConnectionManager::getDefaultPassphrase();

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
        $this->logConnectionAttempt();

        try {
            $this->createConnection();
            $this->authenticateConnection();

            Log::info('Connection established successfully', [
                'host' => $this->host,
                'port' => $this->port,
                'type' => $this->connection instanceof SFTP ? 'SFTP' : 'SSH',
            ]);

            return new Connection($this->connection);
        } catch (Exception $e) {
            $this->logConnectionFailure($e);
            throw ConnectionException::withMessage('Unable to connect to the server. It might be offline or unreachable.');
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
            'privateKey' => $this->privateKey,
        ], fn ($value): bool => $value === null);

        if ($missingDetails !== []) {
            $missingFields = implode(', ', array_keys($missingDetails));
            throw ConnectionException::withMessage("Insufficient connection details provided. Missing: {$missingFields}");
        }
    }

    /**
     * Log the connection attempt.
     */
    protected function logConnectionAttempt(): void
    {
        Log::info('Attempting to establish connection', [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'privateKeyPath' => $this->privateKey,
            'hasPassphrase' => $this->passphrase !== null && $this->passphrase !== '' && $this->passphrase !== '0',
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * Create the underlying connection object.
     *
     * @throws RuntimeException If connection creation fails
     */
    protected function createConnection(): void
    {
        set_error_handler(function ($severity, $message, $file, $line): void {
            throw new RuntimeException($message);
        }, E_WARNING);

        try {
            // First, try to establish an SFTP connection
            $connection = new SFTP((string) $this->host, $this->port, $this->timeout);

            if (! $connection->isConnected()) {

                // If SFTP fails, fall back to SSH2
                $connection = new SSH2((string) $this->host, $this->port, $this->timeout);

                if (! $connection->isConnected()) {
                    throw new RuntimeException("Failed to connect to {$this->host}:{$this->port}");
                }
            }

            $this->connection = $connection;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Authenticate the connection.
     *
     * @throws ConnectionException If authentication fails
     */
    protected function authenticateConnection(): void
    {
        $this->ensureConnectionEstablished();

        $privateKey = $this->loadPrivateKey();

        if (! $this->attemptLogin($privateKey)) {
            throw ConnectionException::authenticationFailed();
        }
    }

    /**
     * Load the private key.
     *
     * @throws ConnectionException If we are unable to load the private key.
     */
    protected function loadPrivateKey(): PrivateKey
    {
        $this->validatePrivateKeyDetails();
        $keyContent = $this->readPrivateKeyFile();

        try {
            $privateKey = PublicKeyLoader::load($keyContent, (string) $this->passphrase);

            if (! $privateKey instanceof PrivateKey) {
                throw new RuntimeException('Invalid private key format.');
            }

            return $privateKey;
        } catch (Exception $e) {
            Log::error('Failed to load private key', ['error' => $e->getMessage()]);
            throw ConnectionException::withMessage('Failed to load private key: ' . $e->getMessage());
        }
    }

    /**
     * Validate private key details.
     *
     * @throws ConnectionException If private key details are invalid
     */
    protected function validatePrivateKeyDetails(): void
    {
        if ($this->privateKey === null) {
            throw ConnectionException::withMessage('Private key path is not set.');
        }

        if ($this->passphrase === null) {
            throw ConnectionException::withMessage('Passphrase is not set.');
        }

        Log::debug('Loading private key', [
            'keyPath' => $this->privateKey,
            'hasPassphrase' => $this->passphrase !== '' && $this->passphrase !== '0',
        ]);
    }

    /**
     * Read the private key file.
     *
     * @throws ConnectionException If unable to read the private key file
     */
    protected function readPrivateKeyFile(): string
    {
        $keyContent = @file_get_contents((string) $this->privateKey);

        if ($keyContent === false) {
            throw ConnectionException::withMessage('Unable to read private key file.');
        }

        return $keyContent;
    }

    /**
     * Log connection failure with appropriate level and details.
     *
     * @param  Exception  $exception  The exception that caused the failure
     */
    protected function logConnectionFailure(Exception $exception): void
    {
        $context = [
            'error' => $exception->getMessage(),
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'timeout' => $this->timeout,
        ];

        $logMethod = $this->determineLogMethod($exception);
        $logMessage = $this->determineLogMessage($exception);

        Log::$logMethod($logMessage, $context);
    }

    /**
     * Determine the appropriate logging method based on the exception.
     *
     * @param  Exception  $exception  The exception that caused the failure
     * @return string The logging method to use
     */
    protected function determineLogMethod(Exception $exception): string
    {
        return match (true) {
            str_contains($exception->getMessage(), 'timed out') => 'warning',
            $exception instanceof ConnectionException && $exception->getMessage() === ConnectionException::authenticationFailed()->getMessage() => 'error',
            default => 'error',
        };
    }

    /**
     * Determine the appropriate log message based on the exception.
     *
     * @param  Exception  $exception  The exception that caused the failure
     * @return string The log message
     */
    protected function determineLogMessage(Exception $exception): string
    {
        return match (true) {
            str_contains($exception->getMessage(), 'timed out') => 'Connection attempt timed out',
            $exception instanceof ConnectionException && $exception->getMessage() === ConnectionException::authenticationFailed()->getMessage() => 'Authentication failed',
            default => 'Connection failed',
        };
    }

    /**
     * Ensure that a valid connection has been established.
     *
     * @throws ConnectionException If no valid connection exists
     */
    private function ensureConnectionEstablished(): void
    {
        if (! $this->connection instanceof SSH2) {
            throw ConnectionException::withMessage('The connection has not been established or has strangely become invalid.');
        }
    }

    /**
     * Attempt to log in to the remote server.
     *
     * @param  PrivateKey  $privateKey  The private key to use for authentication
     * @return bool True if login was successful, false otherwise
     *
     * @throws ConnectionException
     */
    private function attemptLogin(PrivateKey $privateKey): bool
    {
        if ($this->username === null) {
            throw ConnectionException::withMessage('Username is not set');
        }

        $this->ensureConnectionEstablished();

        /** @var SSH2|SFTP $connection */
        $connection = $this->connection;

        $username = (string) $this->username;

        return $connection->login($username, $privateKey);
    }
}
