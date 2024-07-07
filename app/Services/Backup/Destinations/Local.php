<?php

declare(strict_types=1);

namespace App\Services\Backup\Destinations;

use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Destinations\Contracts\BackupDestinationInterface;
use App\Services\Backup\Traits\BackupHelpers;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class Local implements BackupDestinationInterface
{
    use BackupHelpers;

    public function __construct(
        protected SFTPInterface $sftp,
        protected string $storagePath
    ) {
    }

    /**
     * @return array<string>
     */
    public function listFiles(string $pattern): array
    {
        $files = $this->listDirectoryContents($this->storagePath);

        return $this->filterAndSortFiles($files, $pattern);
    }

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
     * @throws Exception
     */
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
     * @param  array<string>  $files
     * @return array<string>
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

    protected function getRelativePath(string $fullPath): string
    {
        return str_replace($this->storagePath . '/', '', $fullPath);
    }

    /**
     * @throws Exception
     */
    protected function performFileStreaming(SFTPInterface $sourceSftp, string $remoteZipPath, string $fullPath): bool
    {
        $tempFile = $this->downloadFileViaSFTP($sourceSftp, $remoteZipPath);
        $content = $this->getFileContents($tempFile);

        return $this->processAndUploadFile($content, $tempFile, $fullPath);
    }

    protected function getFileContents(string $filePath): string|false
    {
        return file_get_contents($filePath);
    }

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

    private function logStartStreaming(string $remoteZipPath, string $fileName, string $fullPath): void
    {
        Log::info('Starting to stream file to remote local storage.', [
            'remote_zip_path' => $remoteZipPath,
            'file_name' => $fileName,
            'full_path' => $fullPath,
        ]);
    }

    private function logSuccessfulStreaming(string $fullPath): void
    {
        Log::info('File successfully streamed to remote local storage.', ['file_path' => $fullPath]);
    }

    /**
     * @throws Exception
     */
    private function getLastModifiedDateTime(string $file): DateTime
    {
        $stat = $this->sftp->stat($file);
        if ($stat === false || ! isset($stat['mtime'])) {
            throw new RuntimeException("Failed to get last modified time for file: {$file}");
        }

        return new DateTime("@{$stat['mtime']}");
    }

    /**
     * @return array<string>
     */
    private function listDirectoryContents(string $directory): array
    {
        $result = $this->sftp->exec("find {$directory} -type f");
        if ($result === false) {
            Log::error('Failed to list directory contents', ['directory' => $directory]);

            return [];
        }

        return array_filter(explode("\n", $result));
    }
}
