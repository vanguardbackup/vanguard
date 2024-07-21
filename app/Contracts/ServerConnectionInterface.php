<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\ConnectionType;
use App\Exceptions\ServerConnectionException;

/**
 * Interface for managing server connections.
 *
 * This interface defines the contract for classes that handle connections to remote servers.
 * It provides methods for connecting, disconnecting, executing commands, and transferring files.
 */
interface ServerConnectionInterface
{
    /**
     * Establish a connection to the remote server.
     *
     * @throws ServerConnectionException If the connection fails
     */
    public function connect(): void;

    /**
     * Disconnect from the remote server.
     */
    public function disconnect(): void;

    /**
     * Execute a command on the remote server.
     *
     * @param  string  $command  The command to execute
     * @return string The output of the executed command
     *
     * @throws ServerConnectionException If the command execution fails
     */
    public function executeCommand(string $command): string;

    /**
     * Upload a file to the remote server.
     *
     * @param  string  $localPath  The path of the file on the local system
     * @param  string  $remotePath  The path where the file should be uploaded on the remote server
     *
     * @throws ServerConnectionException If the file upload fails
     */
    public function uploadFile(string $localPath, string $remotePath): void;

    /**
     * Download a file from the remote server.
     *
     * @param  string  $remotePath  The path of the file on the remote server
     * @param  string  $localPath  The path where the file should be saved on the local system
     *
     * @throws ServerConnectionException If the file download fails
     */
    public function downloadFile(string $remotePath, string $localPath): void;

    /**
     * Set the path to the private key file used for authentication.
     *
     * @param  string  $path  The path to the private key file
     */
    public function setPrivateKeyPath(string $path): self;

    /**
     * Get the type of connection being used.
     *
     * @return ConnectionType The type of connection (e.g., SSH, SFTP)
     */
    public function getConnectionType(): ConnectionType;
}
