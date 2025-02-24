<?php

declare(strict_types=1);

namespace App\Services\Backup\Destinations;

use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Destinations\Contracts\BackupDestinationInterface;
use App\Services\Backup\Traits\BackupHelpers;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Override;
use RuntimeException;

/**
 * Local Backup Destination
 *
 * This class implements the BackupDestinationInterface for managing backups on a local storage
 * accessed via SFTP. It provides functionality for listing, deleting, and streaming files.
 */
class Local implements BackupDestinationInterface
{
    use BackupHelpers;

    /**
     * Constructor for Local backup destination.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface for file operations
     * @param  string  $storagePath  The base storage path for backups
     */
    public function __construct(
        protected SFTPInterface $sftp,
        protected string $storagePath
    ) {}

    /**
     * List files matching the given pattern.
     *
     * @param  string  $pattern  The pattern to match files against
     * @return array<string> An array of file names or paths matching the pattern
     */
    #[Override]
    public function listFiles(string $pattern): array
    {
        $files = $this->listDirectoryContents($this->storagePath);

        return $this->filterAndSortFiles($files, $pattern);
    }

    /**
     * Delete a file from the backup destination.
     *
     * @param  string  $filePath  The path of the file to delete
     */
    #[Override]
    public function deleteFile(string $filePath): void
    {
        $fullPath = $this->getFullPath($filePath);
        if ($this->sftp->delete($fullPath)) {
            Log::info('File deleted from remote local storage.', ['file_path' => $fullPath]);
        } else {
            Log::warning('Failed to delete file from remote local storage.', ['file_path' => $fullPath]);
        }
    }

    /**
     * Stream files from a remote location to the backup destination.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface to use for file transfer
     * @param  string  $remoteZipPath  The path of the remote zip file
     * @param  string  $fileName  The name of the file being transferred
     * @param  string|null  $storagePath  The local path where the file should be stored
     * @param  int  $retries  The number of retry attempts in case of failure (default: 3)
     * @param  int  $delay  The delay between retry attempts in seconds (default: 5)
     * @return bool True if the file was successfully streamed, false otherwise
     *
     * @throws Exception If an error occurs during the streaming process
     */
    #[Override]
    public function streamFiles(
        SFTPInterface $sftp,
        string $remoteZipPath,
        string $fileName,
        ?string $storagePath = null,
        int $retries = 3,
        int $delay = 5
    ): bool {
        $fullPath = $this->getFullPath($fileName, $storagePath);

        $this->logStartStreaming($remoteZipPath, $fileName, $fullPath);

        return $this->retryCommand(
            fn (): bool => $this->performFileStreaming($sftp, $remoteZipPath, $fullPath),
            $retries,
            $delay
        );
    }

    /**
     * Get the full path for a file in the backup destination.
     *
     * @param  string  $fileName  The name of the file
     * @param  string|null  $storagePath  An optional storage path
     * @return string The full path to the file
     */
    public function getFullPath(string $fileName, ?string $storagePath = null): string
    {
        $basePath = $this->normalizePath($this->storagePath);

        if ($storagePath) {
            $storagePath = $this->normalizePath($storagePath);
            if (str_starts_with($storagePath, $basePath)) {
                $storagePath = substr($storagePath, strlen($basePath));
            }

            $relativePath = trim($storagePath, '/') . '/' . $fileName;
        } else {
            $relativePath = $fileName;
        }

        return $this->normalizePath($basePath . '/' . $relativePath);
    }

    /**
     * Ensure a directory exists, creating it if necessary.
     *
     * @param  string  $path  The path of the directory to ensure
     * @return bool True if the directory exists or was created successfully, false otherwise
     */
    public function ensureDirectoryExists(string $path): bool
    {
        $path = $this->normalizePath($path);

        $result = $this->sftp->mkdir($path, 0755, true);

        if ($result) {
            Log::info('Directory created successfully', ['path' => $path]);

            return true;
        }

        $listResult = $this->sftp->exec('ls -la ' . escapeshellarg($path));

        if (! ($listResult === false || ($listResult === '' || $listResult === '0'))) {
            Log::info('Directory already exists', ['path' => $path]);

            return true;
        }

        $lastError = $this->sftp->getLastError();
        Log::error('Failed to create or access directory', [
            'path' => $path,
            'sftp_error' => $lastError,
        ]);

        return false;
    }

    /**
     * Normalizes a file path by converting backslashes to forward slashes,
     * removing duplicate slashes, and trimming trailing slashes.
     *
     * @param  string  $path  The path to normalize
     * @return string The normalized path
     */
    protected function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = (string) preg_replace('#/+#', '/', $path);

        return rtrim($path, '/');
    }

    /**
     * Filter and sort files based on a pattern.
     *
     * @param  array<string>  $files  An array of file paths
     * @param  string  $pattern  The pattern to filter files
     * @return array<string> Filtered and sorted array of file paths
     */
    protected function filterAndSortFiles(array $files, string $pattern): array
    {
        return collect($files)
            ->map(fn ($file): string => $this->getRelativePath($file))
            ->filter(fn ($file): bool => str_contains($file, $pattern))
            ->sortByDesc(fn ($file): DateTime => $this->getLastModifiedDateTime($this->getFullPath($file)))
            ->values()
            ->all();
    }

    /**
     * Get the relative path of a file.
     *
     * @param  string  $fullPath  The full path of the file
     * @return string The relative path
     */
    protected function getRelativePath(string $fullPath): string
    {
        return str_replace($this->storagePath . '/', '', $fullPath);
    }

    /**
     * Perform file streaming from source to destination.
     *
     * @param  SFTPInterface  $sourceSftp  The source SFTP interface
     * @param  string  $remoteZipPath  The path of the remote zip file
     * @param  string  $fullPath  The full path of the destination file
     * @return bool True if streaming was successful, false otherwise
     *
     * @throws Exception If an error occurs during the streaming process
     */
    protected function performFileStreaming(SFTPInterface $sourceSftp, string $remoteZipPath, string $fullPath): bool
    {
        $tempFile = $this->downloadFileViaSFTP($sourceSftp, $remoteZipPath);
        $content = $this->getFileContents($tempFile);

        return $this->processAndUploadFile($content, $tempFile, $fullPath);
    }

    /**
     * Get the contents of a file.
     *
     * @param  string  $filePath  The path of the file
     * @return string|false The contents of the file or false on failure
     */
    protected function getFileContents(string $filePath): string|false
    {
        return file_get_contents($filePath);
    }

    /**
     * Process and upload a file to the destination.
     *
     * @param  string|false  $content  The content of the file
     * @param  string  $tempFile  The path of the temporary file
     * @param  string  $fullPath  The full path of the destination file
     * @return bool True if the file was processed and uploaded successfully, false otherwise
     */
    protected function processAndUploadFile(string|false $content, string $tempFile, string $fullPath): bool
    {
        if ($content === false) {
            Log::error('Failed to read temporary file', ['temp_file' => $tempFile]);

            return false;
        }

        // Ensure the directory exists
        $directory = dirname($fullPath);
        if (! $this->ensureDirectoryExists($directory)) {
            Log::error('Failed to create directory structure', ['directory' => $directory]);

            return false;
        }

        $result = $this->sftp->put($fullPath, $content);
        $this->cleanUpTempFile($tempFile);

        if ($result) {
            $this->logSuccessfulStreaming($fullPath);

            return true;
        }

        $lastError = $this->sftp->getLastError();
        Log::error('Failed to stream file to remote local storage', [
            'full_path' => $fullPath,
            'sftp_error' => $lastError,
        ]);

        return false;
    }

    /**
     * Log the start of file streaming.
     *
     * @param  string  $remoteZipPath  The path of the remote zip file
     * @param  string  $fileName  The name of the file being streamed
     * @param  string  $fullPath  The full path of the destination file
     */
    private function logStartStreaming(string $remoteZipPath, string $fileName, string $fullPath): void
    {
        Log::info('Starting to stream file to remote local storage.', [
            'remote_zip_path' => $remoteZipPath,
            'file_name' => $fileName,
            'full_path' => $fullPath,
        ]);
    }

    /**
     * Log successful file streaming.
     *
     * @param  string  $fullPath  The full path of the streamed file
     */
    private function logSuccessfulStreaming(string $fullPath): void
    {
        Log::info('File successfully streamed to remote local storage.', ['file_path' => $fullPath]);
    }

    /**
     * Get the last modified DateTime of a file.
     *
     * @param  string  $file  The path of the file
     * @return DateTime The last modified DateTime of the file
     *
     * @throws Exception If unable to get the last modified time
     */
    private function getLastModifiedDateTime(string $file): DateTime
    {
        $stat = $this->sftp->stat($file);
        if ($stat === false || ! isset($stat['mtime'])) {
            throw new RuntimeException('Failed to get last modified time for file: ' . $file);
        }

        return new DateTime('@' . $stat['mtime']);
    }

    /**
     * List the contents of a directory.
     *
     * @param  string  $directory  The path of the directory to list
     * @return array<string> An array of file paths in the directory
     */
    private function listDirectoryContents(string $directory): array
    {
        $result = $this->sftp->exec(sprintf('find %s -type f', $directory));
        if ($result === false) {
            Log::error('Failed to list directory contents', ['directory' => $directory]);

            return [];
        }

        return array_filter(explode("\n", (string) $result));
    }
}
