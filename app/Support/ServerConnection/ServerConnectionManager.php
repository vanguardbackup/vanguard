<?php

declare(strict_types=1);

namespace App\Support\ServerConnection;

use App\Models\RemoteServer;
use App\Support\ServerConnection\Fakes\ServerConnectionFake;
use RuntimeException;

/**
 * Manages server connections and provides a fake implementation for testing.
 */
class ServerConnectionManager
{
    /**
     * The default private key path.
     */
    protected ?string $defaultPrivateKey = null;

    /**
     * The default passphrase for the private key.
     */
    protected ?string $defaultPassphrase = null;

    /**
     * The fake server connection instance for testing.
     */
    protected ?ServerConnectionFake $fake = null;

    /**
     * Create a new PendingConnection instance.
     *
     * @param  string  $host  The hostname or IP address
     * @param  int  $port  The port number
     * @param  string  $username  The username
     */
    public function connect(string $host = '', int $port = 22, string $username = 'root'): PendingConnection
    {
        if ($this->fake instanceof ServerConnectionFake) {
            return $this->fake->connect($host, $port, $username);
        }

        $pendingConnection = new PendingConnection;

        if ($this->defaultPrivateKey) {
            $pendingConnection->withPrivateKey($this->defaultPrivateKey, $this->defaultPassphrase);
        }

        if ($host !== '' && $host !== '0') {
            $pendingConnection->connect($host, $port, $username);
        }

        return $pendingConnection;
    }

    /**
     * Create a new PendingConnection instance from a RemoteServer model.
     *
     * @param  RemoteServer  $remoteServer  The RemoteServer model instance
     */
    public function connectFromModel(RemoteServer $remoteServer): PendingConnection
    {
        if ($this->fake instanceof ServerConnectionFake) {
            return $this->fake->connectFromModel($remoteServer);
        }

        return $this->connect()->connectFromModel($remoteServer);
    }

    /**
     * Set the default private key path.
     *
     * @param  string  $path  The path to the private key
     */
    public function defaultPrivateKey(string $path): void
    {
        $this->defaultPrivateKey = $path;
    }

    /**
     * Set the default passphrase.
     *
     * @param  string  $passphrase  The passphrase for the private key
     */
    public function defaultPassphrase(string $passphrase): void
    {
        $this->defaultPassphrase = $passphrase;
    }

    /**
     * Enable fake mode for testing.
     */
    public function fake(): ServerConnectionFake
    {
        return $this->fake = new ServerConnectionFake;
    }

    /**
     * Assert that a connection was established.
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public function assertConnected(): void
    {
        $this->getFake()->assertConnected();
    }

    /**
     * Assert that a connection was disconnected.
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public function assertDisconnected(): void
    {
        $this->getFake()->assertDisconnected();
    }

    /**
     * Assert that a connection was not established.
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public function assertNotConnected(): void
    {
        $this->getFake()->assertNotConnected();
    }

    /**
     * Assert that a command was run.
     *
     * @param  string  $command  The command to assert
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public function assertCommandRan(string $command): void
    {
        $this->getFake()->assertCommandRan($command);
    }

    /**
     * Assert that a file was uploaded.
     *
     * @param  string  $localPath  The local file path
     * @param  string  $remotePath  The remote file path
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public function assertFileUploaded(string $localPath, string $remotePath): void
    {
        $this->getFake()->assertFileUploaded($localPath, $remotePath);
    }

    /**
     * Assert that a file was downloaded.
     *
     * @param  string  $remotePath  The remote file path
     * @param  string  $localPath  The local file path
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public function assertFileDownloaded(string $remotePath, string $localPath): void
    {
        $this->getFake()->assertFileDownloaded($remotePath, $localPath);
    }

    /**
     * Assert that a specific output was produced.
     *
     * @param  string  $output  The expected output
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public function assertOutput(string $output): void
    {
        $this->getFake()->assertOutput($output);
    }

    /**
     * Set the fake connection to succeed.
     */
    public function shouldConnect(): ServerConnectionFake
    {
        if (! $this->fake instanceof ServerConnectionFake) {
            $this->fake = new ServerConnectionFake;
        }

        return $this->getFake()->shouldConnect();
    }

    /**
     * Set the fake connection to fail.
     */
    public function shouldNotConnect(): ServerConnectionFake
    {
        if (! $this->fake instanceof ServerConnectionFake) {
            $this->fake = new ServerConnectionFake;
        }

        return $this->getFake()->shouldConnect();
    }

    /**
     * Assert that a connection was attempted with specific details.
     *
     * @param  array{host: string, port: int, username: string}  $connectionDetails
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public function assertConnectionAttempted(array $connectionDetails): void
    {
        $this->getFake()->assertConnectionAttempted($connectionDetails);
    }

    /**
     * Get the fake instance for assertions.
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    protected function getFake(): ServerConnectionFake
    {
        if (! $this->fake instanceof ServerConnectionFake) {
            throw new RuntimeException('Server connection is not in fake mode.');
        }

        return $this->fake;
    }
}
