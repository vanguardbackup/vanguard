<?php

declare(strict_types=1);

namespace App\Services\Backup\Adapters;

use App\Services\Backup\Contracts\SFTPInterface;
use phpseclib3\Net\SFTP;

/**
 * SFTPAdapter Class
 *
 * This class provides an adapter for SFTP operations using the phpseclib3 library.
 * It implements the SFTPInterface and wraps the functionality of the SFTP class.
 */
class SFTPAdapter implements SFTPInterface
{
    private SFTP $sftp;

    /**
     * Constructor for SFTPAdapter.
     *
     * @param  string  $host  The hostname or IP address of the SFTP server
     * @param  int  $port  The port number for the SFTP connection (default: 22)
     * @param  int  $timeout  The timeout for the connection in seconds (default: 120)
     */
    public function __construct(string $host, int $port = 22, int $timeout = 120)
    {
        $this->sftp = new SFTP($host, $port, $timeout);
    }

    /**
     * Login to the SFTP server.
     *
     * @param  string  $username  The username for authentication
     * @param  mixed  ...$args  Additional arguments for authentication (e.g., password, key)
     * @return bool True if login was successful, false otherwise
     */
    public function login(string $username, mixed ...$args): bool
    {
        return $this->sftp->login($username, ...$args);
    }

    /**
     * Get the last error message from the SFTP connection.
     *
     * @return string The last error message
     */
    public function getLastError(): string
    {
        return $this->sftp->getLastError();
    }

    /**
     * Execute a command on the remote server.
     *
     * @param  string  $command  The command to execute
     * @return bool|string The output of the command if successful, false otherwise
     */
    public function exec(string $command): bool|string
    {
        return $this->sftp->exec($command);
    }

    /**
     * Check if the SFTP connection is currently established.
     *
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->sftp->isConnected();
    }

    /**
     * Upload a file or data to the remote server.
     *
     * @param  string  $remote_file  The path to the remote file
     * @param  string  $data  The data to upload or the path to the local file
     * @param  int  $mode  The source mode (default: SFTP::SOURCE_STRING)
     * @return bool True if the upload was successful, false otherwise
     */
    public function put(string $remote_file, string $data, int $mode = SFTP::SOURCE_STRING): bool
    {
        return $this->sftp->put($remote_file, $data, $mode);
    }

    /**
     * Download a file from the remote server.
     *
     * @param  string  $remote_file  The path to the remote file
     * @param  string|false  $local_file  The path to save the file locally, or false to return the file contents
     * @return bool|string The file contents if $local_file is false, true if the file was successfully saved, or false on failure
     */
    public function get(string $remote_file, string|false $local_file = false): bool|string
    {
        return $this->sftp->get($remote_file, $local_file);
    }

    /**
     * Delete a file or directory on the remote server.
     *
     * @param  string  $path  The path to the file or directory to delete
     * @param  bool  $recursive  Whether to delete directories recursively (default: true)
     * @return bool True if the deletion was successful, false otherwise
     */
    public function delete(string $path, bool $recursive = true): bool
    {
        return $this->sftp->delete($path, $recursive);
    }

    /**
     * Create a directory on the remote server.
     *
     * @param  string  $dir  The path of the directory to create
     * @param  int  $mode  The permissions mode of the new directory (default: -1, which means use the server's default)
     * @param  bool  $recursive  Whether to create parent directories if they don't exist (default: false)
     * @return bool True if the directory was successfully created, false otherwise
     */
    public function mkdir(string $dir, int $mode = -1, bool $recursive = false): bool
    {
        return $this->sftp->mkdir($dir, $mode, $recursive);
    }

    /**
     * Change the permissions of a file or directory on the remote server.
     *
     * @param  int  $mode  The new permissions mode
     * @param  string  $filename  The path to the file or directory
     * @param  bool  $recursive  Whether to change permissions recursively for directories (default: false)
     * @return mixed The result of the chmod operation
     */
    public function chmod(int $mode, string $filename, bool $recursive = false): mixed
    {
        return $this->sftp->chmod($mode, $filename, $recursive);
    }

    /**
     * Get status information about a file on the remote server.
     *
     * @param  string  $filename  The path to the file
     * @return array<string, mixed>|false An array of file information if successful, false otherwise
     */
    public function stat(string $filename): array|false
    {
        return $this->sftp->stat($filename);
    }
}
