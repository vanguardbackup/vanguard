<?php

declare(strict_types=1);

namespace App\Support\ServerConnection\Fakes;

use App\Support\ServerConnection\Connection;
use RuntimeException;

/**
 * A fake implementation of the Connection class for testing purposes.
 *
 * This class simulates the behavior of a real server connection without actually
 * connecting to a remote server. It's useful for unit testing and simulating
 * various server interactions.
 */
class ConnectionFake extends Connection
{
    /**
     * Create a new ConnectionFake instance.
     *
     * @param  ServerConnectionFake  $serverConnectionFake  The fake server connection to use
     */
    public function __construct(protected ServerConnectionFake $serverConnectionFake)
    {
        parent::__construct(null);
    }

    /**
     * Simulate disconnecting from the server.
     */
    public function disconnect(): void
    {
        $this->serverConnectionFake->disconnect();
    }

    /**
     * Simulate running a command on the server.
     *
     * @param  string  $command  The command to run
     * @return string The simulated output of the command
     *
     * @throws RuntimeException If the connection is closed
     */
    public function run(string $command): string
    {
        $this->ensureConnected();
        $this->serverConnectionFake->recordCommand($command);

        return $this->serverConnectionFake->getOutput();
    }

    /**
     * Simulate uploading a file to the server.
     *
     * @param  string  $localPath  The local path of the file to upload
     * @param  string  $remotePath  The remote path where the file should be uploaded
     * @return bool Always returns true to simulate successful upload
     *
     * @throws RuntimeException If the connection is closed
     */
    public function upload(string $localPath, string $remotePath): bool
    {
        $this->ensureConnected();
        $this->serverConnectionFake->recordUpload($localPath, $remotePath);

        return true;
    }

    /**
     * Simulate downloading a file from the server.
     *
     * @param  string  $remotePath  The remote path of the file to download
     * @param  string  $localPath  The local path where the file should be saved
     * @return bool Always returns true to simulate successful download
     *
     * @throws RuntimeException If the connection is closed
     */
    public function download(string $remotePath, string $localPath): bool
    {
        $this->ensureConnected();
        $this->serverConnectionFake->recordDownload($remotePath, $localPath);

        return true;
    }

    /**
     * Check if the fake connection is active.
     *
     * @return bool True if the fake connection is active, false otherwise
     */
    public function connected(): bool
    {
        return $this->serverConnectionFake->isConnected();
    }

    /**
     * Ensure that the fake connection is active before performing an operation.
     *
     * @throws RuntimeException If the fake connection is closed
     */
    protected function ensureConnected(): void
    {
        if (! $this->serverConnectionFake->isConnected()) {
            throw new RuntimeException('Cannot perform operation: Connection is closed.');
        }
    }
}
