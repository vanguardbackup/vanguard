<?php

declare(strict_types=1);

namespace App\Support\ServerConnection;

use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use RuntimeException;

class Connection
{
    /**
     * Create a new Connection instance.
     */
    public function __construct(
        /**
         * The SSH or SFTP connection instance.
         */
        protected SSH2|SFTP|null $ssh2
    ) {}

    /**
     * Check if the connection is active.
     */
    public function connected(): bool
    {
        return $this->ssh2 instanceof SSH2 && $this->ssh2->isConnected() && $this->ssh2->isAuthenticated();
    }

    /**
     * Disconnect from the server.
     */
    public function disconnect(): void
    {
        if ($this->ssh2 instanceof SSH2) {
            $this->ssh2->disconnect();
        }
    }

    /**
     * Run a command on the server.
     *
     * @param  string  $command  The command to execute
     * @return string The command output
     *
     * @throws RuntimeException If command execution fails or connection is null
     */
    public function run(string $command): string
    {
        if (! $this->ssh2 instanceof SSH2) {
            throw new RuntimeException('Cannot execute command: Connection is null');
        }

        if (! $this->ssh2->isConnected() || ! $this->ssh2->isAuthenticated()) {
            throw new RuntimeException('Connection lost. Please re-establish the connection.');
        }

        $output = $this->ssh2->exec($command);

        if ($output === false) {
            throw new RuntimeException("Failed to execute command: {$command}");
        }

        return (string) $output;
    }

    /**
     * Upload a file to the server.
     *
     * @param  string  $localPath  The local file path
     * @param  string  $remotePath  The remote file path
     * @return bool True if upload was successful, false otherwise
     *
     * @throws RuntimeException If the connection is not SFTP or is null
     */
    public function upload(string $localPath, string $remotePath): bool
    {
        if (! $this->ssh2 instanceof SFTP) {
            throw new RuntimeException('SFTP connection required for file upload');
        }

        return $this->ssh2->put($remotePath, $localPath, SFTP::SOURCE_LOCAL_FILE);
    }

    /**
     * Download a file from the server.
     *
     * @param  string  $remotePath  The remote file path
     * @param  string  $localPath  The local file path
     * @return bool True if download was successful, false otherwise
     *
     * @throws RuntimeException If the connection is not SFTP, is null, or download fails
     */
    public function download(string $remotePath, string $localPath): bool
    {
        if (! $this->ssh2 instanceof SFTP) {
            throw new RuntimeException('SFTP connection required for file download');
        }

        $result = $this->ssh2->get($remotePath, $localPath);

        if (! is_bool($result)) {
            throw new RuntimeException("Unexpected result when downloading file: {$remotePath}");
        }

        return $result;
    }
}
