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
    protected static ?string $defaultPrivateKey = null;

    /**
     * The default passphrase for the private key.
     */
    protected static ?string $defaultPassphrase = null;

    /**
     * The fake server connection instance for testing.
     */
    protected static ?ServerConnectionFake $fake = null;

    // Connection Methods

    /**
     * Create a new PendingConnection instance.
     *
     * @param  string  $host  The hostname or IP address
     * @param  int  $port  The port number
     * @param  string  $username  The username
     */
    public static function connect(string $host = '', int $port = 22, string $username = 'root'): PendingConnection
    {
        if (static::$fake instanceof ServerConnectionFake) {
            return static::$fake->connect($host, $port, $username);
        }

        $pendingConnection = new PendingConnection;

        if (static::$defaultPrivateKey) {
            $pendingConnection->withPrivateKey(static::$defaultPrivateKey, static::$defaultPassphrase);
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
    public static function connectFromModel(RemoteServer $remoteServer): PendingConnection
    {
        if (static::$fake instanceof ServerConnectionFake) {
            return static::$fake->connectFromModel($remoteServer);
        }

        return static::connect()->connectFromModel($remoteServer);
    }

    // Configuration Methods

    /**
     * Set the default private key path.
     *
     * @param  string  $path  The path to the private key
     */
    public static function defaultPrivateKey(string $path): void
    {
        static::$defaultPrivateKey = $path;
    }

    /**
     * Set the default passphrase.
     *
     * @param  string  $passphrase  The passphrase for the private key
     */
    public static function defaultPassphrase(string $passphrase): void
    {
        static::$defaultPassphrase = $passphrase;
    }

    /**
     * Get the default private key path.
     *
     * @return string|null The default private key path
     */
    public static function getDefaultPrivateKey(): ?string
    {
        return static::$defaultPrivateKey;
    }

    /**
     * Get the default passphrase for the private key.
     *
     * @return string The default passphrase
     */
    public static function getDefaultPassphrase(): string
    {
        return (string) static::$defaultPassphrase;
    }

    // Fake Implementation Methods

    /**
     * Enable fake mode for testing.
     */
    public static function fake(): ServerConnectionFake
    {
        return static::$fake = new ServerConnectionFake;
    }

    /**
     * Set the fake connection to succeed.
     */
    public static function shouldConnect(): ServerConnectionFake
    {
        if (! static::$fake instanceof ServerConnectionFake) {
            static::$fake = new ServerConnectionFake;
        }

        return static::getFake()->shouldConnect();
    }

    /**
     * Set the fake connection to fail.
     */
    public static function shouldNotConnect(): ServerConnectionFake
    {
        if (! static::$fake instanceof ServerConnectionFake) {
            static::$fake = new ServerConnectionFake;
        }

        return static::getFake()->shouldConnect();
    }

    // Assertion Methods

    /**
     * Assert that a connection was established.
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertConnected(): void
    {
        static::getFake()->assertConnected();
    }

    /**
     * Assert that a connection was disconnected.
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertDisconnected(): void
    {
        static::getFake()->assertDisconnected();
    }

    /**
     * Assert that a connection was not established.
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertNotConnected(): void
    {
        static::getFake()->assertNotConnected();
    }

    /**
     * Assert that a command was run.
     *
     * @param  string  $command  The command to assert
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertCommandRan(string $command): void
    {
        static::getFake()->assertCommandRan($command);
    }

    /**
     * Assert that a file was uploaded.
     *
     * @param  string  $localPath  The local file path
     * @param  string  $remotePath  The remote file path
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertFileUploaded(string $localPath, string $remotePath): void
    {
        static::getFake()->assertFileUploaded($localPath, $remotePath);
    }

    /**
     * Assert that a file was downloaded.
     *
     * @param  string  $remotePath  The remote file path
     * @param  string  $localPath  The local file path
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertFileDownloaded(string $remotePath, string $localPath): void
    {
        static::getFake()->assertFileDownloaded($remotePath, $localPath);
    }

    /**
     * Assert that a specific output was produced.
     *
     * @param  string  $output  The expected output
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertOutput(string $output): void
    {
        static::getFake()->assertOutput($output);
    }

    /**
     * Assert that a connection was attempted with specific details.
     *
     * @param  array{host: string, port: int, username: string}  $connectionDetails
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertConnectionAttempted(array $connectionDetails): void
    {
        static::getFake()->assertConnectionAttempted($connectionDetails);
    }

    /**
     * Get the fake instance for assertions.
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    protected static function getFake(): ServerConnectionFake
    {
        if (! static::$fake instanceof ServerConnectionFake) {
            throw new RuntimeException('Server connection is not in fake mode.');
        }

        return static::$fake;
    }
}
