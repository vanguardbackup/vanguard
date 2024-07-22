<?php

declare(strict_types=1);

namespace App\Support\ServerConnection;

use App\Models\RemoteServer;
use App\Support\ServerConnection\Exceptions\ConnectionException;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

/**
 * Represents a pending connection to a remote server.
 *
 * This class is responsible for configuring and establishing
 * a connection to a remote server using SSH or SFTP.
 */
class PendingConnection
{
    /** @var SSH2|SFTP|null The underlying connection object */
    protected SSH2|SFTP|null $connection = null;

    /** @var string|null The hostname or IP address of the remote server */
    protected ?string $host = null;

    /** @var int The port number for the connection */
    protected int $port = 22;

    /** @var string|null The username for authentication */
    protected ?string $username = null;

    /** @var string|null The password for authentication */
    protected ?string $password = null;

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
        $this->privateKey = $remoteServer->getAttribute('private_key') ?? config('app.ssh.private_key');

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
     * Set the password for authentication.
     *
     * @param  string  $password  The password
     */
    public function withPassword(string $password): self
    {
        $this->password = $password;

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
        $this->passphrase = $passphrase ?? config('app.ssh.passphrase');

        return $this;
    }

    /**
     * Establish the connection.
     *
     * @throws ConnectionException If unable to connect or authenticate
     */
    public function establish(): Connection
    {
        if (! $this->host || ! $this->username) {
            throw ConnectionException::withMessage('Insufficient connection details provided.');
        }

        $this->connection = $this->createConnection();

        if ($this->privateKey) {
            $key = $this->loadPrivateKey();
            $result = $this->connection->login($this->username, $key);
        } else {
            $result = $this->connection->login($this->username, $this->password);
        }

        if (! $result) {
            throw ConnectionException::authenticationFailed();
        }

        return new Connection($this->connection);
    }

    /**
     * Create the underlying connection object.
     */
    protected function createConnection(): SSH2|SFTP
    {
        return new SFTP((string) $this->host, $this->port, $this->timeout);
    }

    /**
     * Load the private key.
     *
     * @throws ConnectionException If unable to load the private key
     */
    protected function loadPrivateKey(): PrivateKey
    {
        if ($this->privateKey === null) {
            throw ConnectionException::withMessage('Private key path is not set.');
        }

        $keyContent = @file_get_contents($this->privateKey);
        if ($keyContent === false) {
            throw ConnectionException::withMessage('Unable to read private key file.');
        }

        $privateKey = RSA::loadPrivateKey($keyContent, (string) $this->passphrase);
        if (! $privateKey instanceof PrivateKey) {
            throw ConnectionException::withMessage('Invalid private key format.');
        }

        return $privateKey;
    }
}
