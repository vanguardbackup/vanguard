<?php

declare(strict_types=1);

namespace App\Support\ServerConnection;

use App\Models\RemoteServer;
use App\Support\ServerConnection\Fakes\ServerConnectionFake;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Log;
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

    /**
     * Indicates whether the fake implementation should be used.
     */
    protected static bool $usesFake = false;

    /**
     * The filename of the SSH key, both the private and public key name.
     */
    public const string SSH_KEY_FILE_NAME = 'key';

    /**
     * The extension of the public key file.
     */
    public const string SSH_KEY_PUBLIC_EXT = 'pub';

    /**
     * Create a new PendingConnection instance.
     *
     * @param  string  $host  The hostname or IP address
     * @param  int  $port  The port number
     * @param  string  $username  The username
     */
    public static function connect(string $host = '', int $port = 22, string $username = 'root'): PendingConnection
    {
        if (static::isFake()) {
            return static::getFake()->connect($host, $port, $username);
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
        if (static::isFake()) {
            return static::getFake()->connectFromModel($remoteServer);
        }

        return static::connect()->connectFromModel($remoteServer);
    }

    /**
     * Set the default private key path.
     *
     * @param  string  $path  The path to the private key file
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
     * @return string The full path to the default private key
     *
     * @throws RuntimeException If the default private key path is not set
     */
    public static function getDefaultPrivateKeyPath(): string
    {
        if (static::isFake()) {
            return static::getFake()->getDefaultPrivateKeyPath();
        }

        if (! static::$defaultPrivateKey) {
            throw new RuntimeException('Default private key path is not set.');
        }

        return static::$defaultPrivateKey . '/' . self::SSH_KEY_FILE_NAME;
    }

    /**
     * Get the path to the default public key file.
     *
     * @return string The full path to the default public key
     *
     * @throws RuntimeException If the default private key path is not set
     */
    public static function getDefaultPublicKeyPath(): string
    {
        if (! static::$defaultPrivateKey) {
            throw new RuntimeException('Default private key path is not set.');
        }

        return static::$defaultPrivateKey . '/' . self::SSH_KEY_FILE_NAME . '.' . self::SSH_KEY_PUBLIC_EXT;
    }

    /**
     * Get the content of the default private key.
     *
     * @return string The content of the default private key
     *
     * @throws RuntimeException|FileNotFoundException If the private key file cannot be found
     */
    public static function getDefaultPrivateKey(): string
    {
        if (static::isFake()) {
            return static::getFake()->getDefaultPrivateKey();
        }

        if (! static::$defaultPrivateKey) {
            throw new RuntimeException('Default private key path is not set.');
        }

        return static::getPrivateKeyContent(static::$defaultPrivateKey . '/' . self::SSH_KEY_FILE_NAME);
    }

    /**
     * Get the default public key.
     *
     * @return string The content of the default public key
     *
     * @throws RuntimeException|FileNotFoundException If the public key file cannot be found
     */
    public static function getDefaultPublicKey(): string
    {
        if (static::isFake()) {
            return static::getFake()->getDefaultPublicKey();
        }

        if (! static::$defaultPrivateKey) {
            throw new RuntimeException('Default private key path is not set.');
        }

        $publicKeyPath = static::$defaultPrivateKey . '/' . self::SSH_KEY_FILE_NAME . '.' . self::SSH_KEY_PUBLIC_EXT;

        return static::getPublicKeyContent($publicKeyPath);
    }

    /**
     * Get the default passphrase for the private key.
     *
     * @return string The default passphrase
     */
    public static function getDefaultPassphrase(): string
    {
        if (static::isFake()) {
            return static::getFake()->getDefaultPassphrase();
        }

        return (string) static::$defaultPassphrase;
    }

    /**
     * Get the content of a private key file.
     *
     * @param  string  $path  The path to the private key file
     * @return string The content of the private key file
     *
     * @throws RuntimeException If the private key file cannot be found or read
     */
    public static function getPrivateKeyContent(string $path): string
    {
        if (static::isFake() || App::environment('testing')) {
            return static::isFake()
                ? static::getFake()->getPrivateKeyContent($path)
                : 'fake_private_key_content_for_testing';
        }

        if (! file_exists($path)) {
            throw new RuntimeException("Private key file does not exist: {$path}");
        }

        Log::debug('The private key path is:', ['path' => $path]);

        return (string) file_get_contents($path);
    }

    /**
     * Get the content of a public key file.
     *
     * @param  string  $path  The path to the public key file
     * @return string The content of the public key file
     *
     * @throws RuntimeException|FileNotFoundException If the public key file cannot be found or read
     */
    public static function getPublicKeyContent(string $path): string
    {
        if (static::isFake()) {
            return static::getFake()->getPublicKeyContent($path);
        }

        if (File::missing($path)) {
            throw new RuntimeException("Public key file does not exist: {$path}");
        }

        return File::get($path);
    }

    /**
     * Enable fake mode for testing with optional initial setup.
     *
     * @param  callable|null  $setup  A function to set up the fake
     */
    public static function fake(?callable $setup = null): ServerConnectionFake
    {
        static::$fake = new ServerConnectionFake;

        if ($setup) {
            $setup(static::$fake);
        }

        static::usesFake();

        return static::$fake;
    }

    /**
     * Set whether to use the fake implementation.
     *
     * @param  bool  $use  Whether to use the fake
     */
    public static function usesFake(bool $use = true): void
    {
        static::$usesFake = $use;
    }

    /**
     * Check if the fake implementation is being used.
     *
     * @return bool Whether the fake is being used
     */
    public static function isFake(): bool
    {
        return static::$usesFake && static::$fake instanceof ServerConnectionFake;
    }

    /**
     * Reset the manager to its initial state.
     */
    public static function reset(): void
    {
        static::$fake = null;
        static::$usesFake = false;
        static::$defaultPrivateKey = null;
        static::$defaultPassphrase = null;
    }

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
     * Assert that a command was run or that any command was run.
     *
     * @param  string|null  $command  The command to assert, or null to check if any command was run
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertCommandRan(?string $command = null): void
    {
        static::getFake()->assertCommandRan($command);
    }

    /**
     * Assert that no commands were run.
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertNoCommandsRan(): void
    {
        static::getFake()->assertNoCommandsRan();
    }

    /**
     * Assert that any command was run.
     *
     * This is an alias for calling assertCommandRan() without arguments.
     * It provides a more expressive way to check if any command was executed.
     *
     * @throws RuntimeException If server connection is not in fake mode
     */
    public static function assertAnyCommandRan(): void
    {
        static::getFake()->assertAnyCommandRan();
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
     * @throws RuntimeException If server connection is not in fake mode or if the fake instance is not set
     */
    protected static function getFake(): ServerConnectionFake
    {
        if (! static::isFake()) {
            throw new RuntimeException('Server connection is not in fake mode.');
        }

        if (! static::$fake instanceof ServerConnectionFake) {
            throw new RuntimeException('Fake instance is not set. Call ServerConnectionManager::fake() first.');
        }

        return static::$fake;
    }
}
