<?php

declare(strict_types=1);

namespace App\Support\ServerConnection\Concerns;

use App\Support\ServerConnection\Exceptions\ConnectionException;

/**
 * Trait ManagesFiles
 *
 * This trait provides methods for managing files on a remote server.
 */
trait ManagesFiles
{
    /**
     * Upload data to a file on the server.
     *
     * @param  string  $remotePath  The remote path of the file
     * @param  string  $data  The data to upload (can be a string or a stream resource)
     * @return bool Whether the operation was successful
     *
     * @throws ConnectionException If the connection is not established
     */
    public function put(string $remotePath, string $data): bool
    {
        $this->ensureConnected();

        return $this->connection->put($remotePath, $data);
    }

    /**
     * Download a file from the server.
     *
     * @param  string  $remotePath  The remote path of the file
     * @return string|false The file contents or false on error
     *
     * @throws ConnectionException If the connection is not established
     */
    public function get(string $remotePath): string|false
    {
        $this->ensureConnected();

        return $this->connection->get($remotePath);
    }

    /**
     * List files in a directory on the server.
     *
     * @param  string  $remotePath  The remote path of the directory
     * @return array<string>|false An array of files or false on error
     *
     * @throws ConnectionException If the connection is not established
     */
    public function listDirectory(string $remotePath): array|false
    {
        $this->ensureConnected();

        return $this->connection->nlist($remotePath);
    }

    /**
     * Delete a file on the server.
     *
     * @param  string  $remotePath  The remote path of the file
     * @return bool Whether the operation was successful
     *
     * @throws ConnectionException If the connection is not established
     */
    public function delete(string $remotePath): bool
    {
        $this->ensureConnected();

        return $this->connection->delete($remotePath);
    }

    /**
     * Rename a file on the server.
     *
     * @param  string  $from  The current name of the file
     * @param  string  $to  The new name of the file
     * @return bool Whether the operation was successful
     *
     * @throws ConnectionException If the connection is not established
     */
    public function rename(string $from, string $to): bool
    {
        $this->ensureConnected();

        return $this->connection->rename($from, $to);
    }

    /**
     * Get file stats.
     *
     * @param  string  $remotePath  The remote path of the file
     * @return array<string, mixed>|false An array of file stats or false on error
     *
     * @throws ConnectionException If the connection is not established
     */
    public function stat(string $remotePath): array|false
    {
        $this->ensureConnected();

        return $this->connection->stat($remotePath);
    }

    /**
     * Ensure that the connection is established before performing an operation.
     *
     * @throws ConnectionException If the connection is not established
     */
    private function ensureConnected(): void
    {
        if (! $this->isConnected()) {
            throw ConnectionException::withMessage('No active connection. Please connect first.');
        }
    }
}
