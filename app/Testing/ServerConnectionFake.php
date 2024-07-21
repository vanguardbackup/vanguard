<?php

declare(strict_types=1);

namespace App\Testing;

use App\Contracts\ServerConnectionInterface;
use App\Enums\ConnectionType;
use App\Exceptions\ServerConnectionException;
use App\Models\RemoteServer;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * ServerConnectionFake class for simulating server connections in tests.
 *
 * This class implements the ServerConnectionInterface and provides methods
 * to fake server connections, command executions, file uploads, and downloads.
 * It's designed to be used in unit tests to avoid actual server interactions.
 */
class ServerConnectionFake implements ServerConnectionInterface
{
    private bool $isConnected = false;
    private ?RemoteServer $remoteServer = null;
    /** @var array<string, string> */
    private array $commandResponses = [];
    /** @var array<int, string> */
    private array $executedCommands = [];
    /** @var array<string, string> */
    private array $uploadedFiles = [];
    /** @var array<string, string> */
    private array $downloadedFiles = [];
    private ?string $connectException = null;
    private ?string $privateKeyPath = null;

    /**
     * Constructor for ServerConnectionFake.
     *
     * Binds this instance to the service container for easy retrieval in tests.
     */
    public function __construct()
    {
        App::instance(__CLASS__, $this);
    }

    /**
     * Assert that a connection has been established.
     *
     * @throws ExpectationFailedException
     */
    public static function assertConnected(): void
    {
        $serverConnectionFake = app(__CLASS__);
        PHPUnit::assertTrue($serverConnectionFake->isConnected, 'Expected connection to be successful, but it was not.');
    }

    /**
     * Assert that a connection has not been established.
     *
     * @throws ExpectationFailedException
     */
    public static function assertNotConnected(): void
    {
        $serverConnectionFake = app(__CLASS__);
        PHPUnit::assertFalse($serverConnectionFake->isConnected, 'Expected connection to fail, but it was successful.');
    }

    /**
     * Assert that a connection has been established to a specific server.
     *
     * @param  callable  $callback  A callback to validate the connected server
     *
     * @throws ExpectationFailedException
     */
    public static function assertConnectedTo(callable $callback): void
    {
        $serverConnectionFake = app(__CLASS__);
        PHPUnit::assertTrue($serverConnectionFake->isConnected, 'Expected to be connected, but no connection was established.');
        PHPUnit::assertNotNull($serverConnectionFake->remoteServer, 'Connected server information is not available.');
        PHPUnit::assertTrue($callback($serverConnectionFake->remoteServer), 'The connected server does not match the expected criteria.');
    }

    /**
     * Assert that a specific command has been executed.
     *
     * @param  string  $command  The command to check for execution
     *
     * @throws ExpectationFailedException
     */
    public static function assertCommandExecuted(string $command): void
    {
        $serverConnectionFake = app(__CLASS__);
        PHPUnit::assertContains($command, $serverConnectionFake->executedCommands, "The command '{$command}' was not executed.");
    }

    /**
     * Assert that a file has been uploaded.
     *
     * @param  string  $localPath  The expected local path of the uploaded file
     * @param  string  $remotePath  The expected remote path of the uploaded file
     *
     * @throws ExpectationFailedException
     */
    public static function assertFileUploaded(string $localPath, string $remotePath): void
    {
        $serverConnectionFake = app(__CLASS__);
        PHPUnit::assertArrayHasKey($localPath, $serverConnectionFake->uploadedFiles, "File was not uploaded from '{$localPath}'.");
        PHPUnit::assertEquals($remotePath, $serverConnectionFake->uploadedFiles[$localPath], "File from '{$localPath}' was not uploaded to '{$remotePath}'.");
    }

    /**
     * Assert that a file has been downloaded.
     *
     * @param  string  $remotePath  The expected remote path of the downloaded file
     * @param  string  $localPath  The expected local path where the file was downloaded
     *
     * @throws ExpectationFailedException
     */
    public static function assertFileDownloaded(string $remotePath, string $localPath): void
    {
        $serverConnectionFake = app(__CLASS__);
        PHPUnit::assertArrayHasKey($remotePath, $serverConnectionFake->downloadedFiles, "File was not downloaded from '{$remotePath}'.");
        PHPUnit::assertEquals($localPath, $serverConnectionFake->downloadedFiles[$remotePath], "File from '{$remotePath}' was not downloaded to '{$localPath}'.");
    }

    /**
     * Simulate connecting to a server.
     *
     * @throws ServerConnectionException If connection is set to fail or private key is not set
     */
    public function connect(): void
    {
        if ($this->connectException) {
            throw new ServerConnectionException($this->connectException);
        }

        if ($this->privateKeyPath === null) {
            throw new ServerConnectionException('Private key path not set. Call setPrivateKeyPath() first.');
        }

        $this->isConnected = true;
    }

    /**
     * Simulate disconnecting from a server.
     */
    public function disconnect(): void
    {
        $this->isConnected = false;
        $this->remoteServer = null;
    }

    /**
     * Simulate executing a command on the server.
     *
     * @param  string  $command  The command to execute
     * @return string The simulated command output
     *
     * @throws ServerConnectionException If not connected
     */
    public function executeCommand(string $command): string
    {
        if (! $this->isConnected) {
            throw new ServerConnectionException('Not connected. Call connect() first.');
        }
        $this->executedCommands[] = $command;

        return $this->commandResponses[$command] ?? '';
    }

    /**
     * Simulate uploading a file to the server.
     *
     * @param  string  $localPath  The local path of the file
     * @param  string  $remotePath  The remote path to upload to
     *
     * @throws ServerConnectionException If not connected
     */
    public function uploadFile(string $localPath, string $remotePath): void
    {
        if (! $this->isConnected) {
            throw new ServerConnectionException('Not connected. Call connect() first.');
        }
        $this->uploadedFiles[$localPath] = $remotePath;
    }

    /**
     * Simulate downloading a file from the server.
     *
     * @param  string  $remotePath  The remote path of the file
     * @param  string  $localPath  The local path to save the file
     *
     * @throws ServerConnectionException If not connected
     */
    public function downloadFile(string $remotePath, string $localPath): void
    {
        if (! $this->isConnected) {
            throw new ServerConnectionException('Not connected. Call connect() first.');
        }
        $this->downloadedFiles[$remotePath] = $localPath;
    }

    /**
     * Set the private key path for the connection.
     *
     * @param  string  $path  The path to the private key
     */
    public function setPrivateKeyPath(string $path): self
    {
        $this->privateKeyPath = $path;

        return $this;
    }

    /**
     * Get the connection type.
     *
     * @return ConnectionType Always returns SSH for this fake
     */
    public function getConnectionType(): ConnectionType
    {
        return ConnectionType::SSH;
    }

    /**
     * Configure whether the connection should succeed or fail.
     *
     * @param  bool  $should  Whether the connection should succeed
     * @param  string|null  $exceptionMessage  The exception message if connection should fail
     */
    public function shouldConnect(bool $should = true, ?string $exceptionMessage = null): self
    {
        $this->isConnected = $should;
        $this->connectException = $exceptionMessage;

        return $this;
    }

    /**
     * Set a predefined response for a command.
     *
     * @param  string  $command  The command to set a response for
     * @param  string  $response  The response to return for the command
     */
    public function withCommandResponse(string $command, string $response): self
    {
        $this->commandResponses[$command] = $response;

        return $this;
    }

    /**
     * Set the connected server for this fake connection.
     *
     * @param  RemoteServer  $remoteServer  The server to set as connected
     */
    public function setConnectedServer(RemoteServer $remoteServer): self
    {
        $this->remoteServer = $remoteServer;

        return $this;
    }
}
