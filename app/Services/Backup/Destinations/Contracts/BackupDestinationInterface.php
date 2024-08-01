<?php

declare(strict_types=1);

namespace App\Services\Backup\Destinations\Contracts;

use App\Services\Backup\Contracts\SFTPInterface;
use RuntimeException;

/**
 * BackupDestinationInterface
 *
 * This interface defines the contract for backup destination operations.
 * Implementations of this interface should provide functionality for
 * managing files in a backup destination, including listing, deleting,
 * and streaming files.
 */
interface BackupDestinationInterface
{
    /**
     * List files matching the given pattern.
     *
     * @param  string  $pattern  The pattern to match files against
     * @return array<string> An array of file names or paths matching the pattern
     */
    public function listFiles(string $pattern): array;

    /**
     * Delete a file from the backup destination.
     *
     * @param  string  $filePath  The path of the file to delete
     *
     * @throws RuntimeException If the file cannot be deleted
     */
    public function deleteFile(string $filePath): void;

    /**
     * Stream files from a remote location to the backup destination.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface to use for file transfer
     * @param  string  $remoteZipPath  The path of the remote zip file
     * @param  string  $fileName  The name of the file being transferred
     * @param  string  $storagePath  The local path where the file should be stored
     * @param  int  $retries  The number of retry attempts in case of failure (default: 3)
     * @param  int  $delay  The delay between retry attempts in seconds (default: 5)
     * @return bool True if the file was successfully streamed, false otherwise
     *
     * @throws RuntimeException If the file cannot be streamed after all retry attempts
     */
    public function streamFiles(SFTPInterface $sftp, string $remoteZipPath, string $fileName, string $storagePath, int $retries = 3, int $delay = 5): bool;
}
