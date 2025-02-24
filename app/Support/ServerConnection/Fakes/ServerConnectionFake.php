<?php

declare(strict_types=1);

namespace App\Support\ServerConnection\Fakes;

use App\Models\RemoteServer;
use App\Support\ServerConnection\Connection;
use App\Support\ServerConnection\Exceptions\ConnectionException;
use App\Support\ServerConnection\PendingConnection;
use Override;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Fake implementation of ServerConnection for testing purposes.
 */
class ServerConnectionFake extends PendingConnection
{
    /**
     * Indicates if the connection was ever established.
     */
    protected bool $wasEverConnected = false;

    /**
     * Indicates if the connection is currently active.
     */
    protected bool $isCurrentlyConnected = false;

    /**
     * Indicates if the connection should be established when attempted.
     */
    protected bool $shouldConnect = true;

    /**
     * List of connection attempts made.
     *
     * @var array<array{host: string, port: int, username: string}>
     */
    protected array $connectionAttempts = [];

    /**
     * List of commands that were run.
     *
     * @var array<string>
     */
    protected array $commands = [];

    /**
     * List of uploaded files.
     *
     * @var array<array{localPath: string, remotePath: string}>
     */
    protected array $uploads = [];

    /**
     * List of downloaded files.
     *
     * @var array<array{remotePath: string, localPath: string}>
     */
    protected array $downloads = [];

    /**
     * The simulated command output.
     */
    protected string $output = '';

    /**
     * Simulated connection timeout in seconds.
     */
    protected int $timeout = 30;

    /**
     * Fake private key content.
     */
    protected string $fakePrivateKey = 'fake_private_key_content';

    /**
     * Fake public key content.
     */
    protected string $fakePublicKey = 'fake_public_key_content';

    /**
     * Fake passphrase.
     */
    protected string $fakePassphrase = 'fake_passphrase';

    /**
     * Simulate connecting from a RemoteServer model.
     *
     * @param  RemoteServer  $remoteServer  The RemoteServer model instance
     * @return $this
     */
    #[Override]
    public function connectFromModel(RemoteServer $remoteServer): self
    {
        $this->connectionAttempts[] = [
            'host' => $remoteServer->getAttribute('ip_address'),
            'port' => (int) $remoteServer->getAttribute('port'),
            'username' => $remoteServer->getAttribute('username'),
        ];

        return $this;
    }

    /**
     * Simulate connecting to a server.
     *
     * @param  string  $host  The hostname or IP address
     * @param  int  $port  The port number
     * @param  string  $username  The username
     * @return $this
     */
    #[Override]
    public function connect(string $host = '', int $port = 22, string $username = 'root'): self
    {
        $this->connectionAttempts[] = ['host' => $host, 'port' => $port, 'username' => $username];

        return $this;
    }

    /**
     * Simulate establishing a connection.
     *
     * @throws ConnectionException If the connection should fail
     */
    #[Override]
    public function establish(): Connection
    {
        if (! $this->shouldConnect) {
            throw ConnectionException::connectionFailed();
        }

        $this->wasEverConnected = true;
        $this->isCurrentlyConnected = true;

        return new ConnectionFake($this);
    }

    /**
     * Set the connection to succeed.
     *
     * @return $this
     */
    public function shouldConnect(): self
    {
        $this->shouldConnect = true;

        return $this;
    }

    /**
     * Set the connection to fail.
     *
     * @return $this
     */
    public function shouldNotConnect(): self
    {
        $this->shouldConnect = false;

        return $this;
    }

    /**
     * Simulate disconnecting from the server.
     */
    public function disconnect(): void
    {
        $this->isCurrentlyConnected = false;
    }

    /**
     * Simulate setting the connection timeout.
     *
     * @param  int  $seconds  The timeout in seconds
     * @return $this
     */
    #[Override]
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Simulate setting the private key for authentication.
     *
     * @param  string|null  $privateKeyPath  The path to the private key file
     * @param  string|null  $passphrase  The passphrase for the private key
     * @return $this
     */
    #[Override]
    public function withPrivateKey(?string $privateKeyPath = null, ?string $passphrase = null): self
    {
        // Simulate setting private key
        return $this;
    }

    /**
     * Record a command that was run.
     *
     * @param  string  $command  The command to record
     */
    public function recordCommand(string $command): void
    {
        $this->commands[] = $command;
    }

    /**
     * Record a file upload.
     *
     * @param  string  $localPath  The local file path
     * @param  string  $remotePath  The remote file path
     */
    public function recordUpload(string $localPath, string $remotePath): void
    {
        $this->uploads[] = ['localPath' => $localPath, 'remotePath' => $remotePath];
    }

    /**
     * Record a file download.
     *
     * @param  string  $remotePath  The remote file path
     * @param  string  $localPath  The local file path
     */
    public function recordDownload(string $remotePath, string $localPath): void
    {
        $this->downloads[] = ['remotePath' => $remotePath, 'localPath' => $localPath];
    }

    /**
     * Assert that a connection was established.
     *
     * @throws ExpectationFailedException
     */
    public function assertConnected(): void
    {
        PHPUnit::assertTrue($this->wasEverConnected, 'Failed asserting that a connection was ever established.');
    }

    /**
     * Assert that a connection was not established.
     *
     * @throws ExpectationFailedException
     */
    public function assertNotConnected(): void
    {
        PHPUnit::assertFalse($this->isCurrentlyConnected, 'Failed asserting that a connection was not established.');
    }

    /**
     * Assert that the connection was disconnected.
     *
     * @throws ExpectationFailedException
     */
    public function assertDisconnected(): void
    {
        PHPUnit::assertFalse($this->isCurrentlyConnected, 'Failed asserting that the connection was disconnected.');
    }

    /**
     * Assert that a connection was attempted with specific details.
     *
     * @param  array{host: string, port: int, username: string}  $connectionDetails
     *
     * @throws ExpectationFailedException
     */
    public function assertConnectionAttempted(array $connectionDetails): void
    {
        $connectionDetails['port'] = (int) $connectionDetails['port'];

        PHPUnit::assertContains($connectionDetails, $this->connectionAttempts, 'Failed asserting that a connection was attempted with the given details.');
    }

    /**
     * Assert that a specific command was run, or that any command was run if no specific command is provided.
     *
     * This method can be used in two ways:
     * 1. To check if a specific command was run by passing the command as an argument.
     * 2. To check if any command was run by calling the method without arguments.
     *
     * @param  string|null  $command  The command to assert, or null to check if any command was run
     *
     * @throws ExpectationFailedException
     */
    public function assertCommandRan(?string $command = null): void
    {
        $message = $command === null ? 'Any command' : "The command [{$command}]";

        PHPUnit::assertNotEmpty($this->commands, "{$message} was not run.");

        if ($command !== null) {
            PHPUnit::assertContains($command, $this->commands, "The command [{$command}] was not run.");
        }
    }

    /**
     * Assert that no commands were run.
     *
     * @throws ExpectationFailedException
     */
    public function assertNoCommandsRan(): void
    {
        PHPUnit::assertEmpty($this->commands, 'Commands were run when none were expected.');
    }

    /**
     * Assert that any command was run.
     *
     * This is an alias for calling assertCommandRan() without arguments.
     * It provides a more expressive way to check if any command was executed.
     *
     * @throws ExpectationFailedException
     */
    public function assertAnyCommandRan(): void
    {
        $this->assertCommandRan();
    }

    /**
     * Assert that a specific file was uploaded.
     *
     * @param  string  $localPath  The local file path
     * @param  string  $remotePath  The remote file path
     *
     * @throws ExpectationFailedException
     */
    public function assertFileUploaded(string $localPath, string $remotePath): void
    {
        PHPUnit::assertContains(
            ['localPath' => $localPath, 'remotePath' => $remotePath],
            $this->uploads,
            "The file [{$localPath}] was not uploaded to [{$remotePath}]."
        );
    }

    /**
     * Assert that a specific file was downloaded.
     *
     * @param  string  $remotePath  The remote file path
     * @param  string  $localPath  The local file path
     *
     * @throws ExpectationFailedException
     */
    public function assertFileDownloaded(string $remotePath, string $localPath): void
    {
        PHPUnit::assertContains(
            ['remotePath' => $remotePath, 'localPath' => $localPath],
            $this->downloads,
            "The file [{$remotePath}] was not downloaded to [{$localPath}]."
        );
    }

    /**
     * Assert that a specific output was produced.
     *
     * @param  string  $output  The expected output
     *
     * @throws ExpectationFailedException
     */
    public function assertOutput(string $output): void
    {
        PHPUnit::assertEquals($output, $this->output, 'The command output does not match.');
    }

    /**
     * Set the simulated command output.
     *
     * @param  string  $output  The output to set
     */
    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    /**
     * Get the simulated command output.
     *
     * @return string The current output
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * Check if the connection is established.
     *
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->isCurrentlyConnected;
    }

    /**
     * Get the default private key content.
     *
     * @return string The fake private key content
     */
    public function getDefaultPrivateKey(): string
    {
        return $this->fakePrivateKey;
    }

    /**
     * Get the default public key content.
     *
     * @return string The fake public key content
     */
    public function getDefaultPublicKey(): string
    {
        return $this->fakePublicKey;
    }

    /**
     * Get the default passphrase.
     *
     * @return string The fake passphrase
     */
    public function getDefaultPassphrase(): string
    {
        return $this->fakePassphrase;
    }

    /**
     * Get the content of a private key file.
     *
     * @param  string  $path  The path to the private key file
     * @return string The fake content of the private key file
     */
    public function getPrivateKeyContent(string $path): string
    {
        return $this->fakePrivateKey;
    }

    /**
     * Get the content of a public key file.
     *
     * @param  string  $path  The path to the public key file
     * @return string The fake content of the public key file
     */
    public function getPublicKeyContent(string $path): string
    {
        return $this->fakePublicKey;
    }

    /**
     * Get the default private key path.
     *
     * @return string The fake path to the private key
     */
    public function getDefaultPrivateKeyPath(): string
    {
        return 'fake/path/to/private/key';
    }
}
