<?php

declare(strict_types=1);

namespace App\Services\Backup\Traits;

use App\Services\Backup\Contracts\SFTPInterface;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * BackupHelpers Trait
 *
 * This trait provides utility methods for backup operations, including
 * command retries, SFTP operations, file management, and logging.
 */
trait BackupHelpers
{
    /**
     * Retry a command multiple times with a delay between attempts.
     *
     * @param  callable  $command  The command to execute
     * @param  int  $maxRetries  Maximum number of retry attempts
     * @param  int  $retryDelay  Delay in seconds between retry attempts
     * @return mixed The result of the command if successful, false otherwise
     */
    public function retryCommand(callable $command, int $maxRetries, int $retryDelay): mixed
    {
        $attempt = 0;
        $result = false;

        while ($attempt < $maxRetries) {
            $result = $command();
            if ($result !== false) {
                break;
            }

            Log::warning('Command failed, retrying...', ['attempt' => $attempt + 1, 'max_retries' => $maxRetries]);
            sleep($retryDelay);
            $attempt++;
        }

        return $result;
    }

    /**
     * Download a file via SFTP.
     *
     * @param  SFTPInterface  $sftp  The SFTP interface to use
     * @param  string  $remoteZipPath  The path of the remote file to download
     * @return string The path to the downloaded temporary file
     *
     * @throws Exception If the download fails
     */
    protected function downloadFileViaSFTP(SFTPInterface $sftp, string $remoteZipPath): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'sftp');
        if (! $sftp->get($remoteZipPath, $tempFile)) {
            $error = $sftp->getLastError();
            Log::error('Failed to download the remote file.', ['remote_zip_path' => $remoteZipPath, 'error' => $error]);
            throw new Exception('Failed to download the remote file: ' . $error);
        }

        Log::debug('Remote file downloaded.', ['temp_file' => $tempFile]);

        return $tempFile;
    }

    /**
     * Open a file as a stream.
     *
     * @param  string  $tempFile  The path to the file to open
     * @return resource The opened file stream
     *
     * @throws Exception If the file cannot be opened as a stream
     */
    protected function openFileAsStream(string $tempFile): mixed
    {
        $stream = fopen($tempFile, 'rb+');
        if (! $stream) {
            $error = error_get_last();
            Log::error('Failed to open the temporary file as a stream.', ['temp_file' => $tempFile, 'error' => $error]);
            throw new Exception('Failed to open the temporary file as a stream: ' . json_encode($error));
        }

        Log::debug('Temporary file opened as a stream.');

        return $stream;
    }

    /**
     * Clean up a temporary file.
     *
     * @param  string  $tempFile  The path to the temporary file to delete
     */
    protected function cleanUpTempFile(string $tempFile): void
    {
        if (file_exists($tempFile)) {
            unlink($tempFile);
            Log::debug('Temporary file deleted.', ['temp_file' => $tempFile]);
        }
    }

    /**
     * Log an info message.
     *
     * @param  string  $message  The message to log
     * @param  array<string, mixed>  $context  Additional context data
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * Log a debug message.
     *
     * @param  string  $message  The message to log
     * @param  array<string, mixed>  $context  Additional context data
     */
    protected function logDebug(string $message, array $context = []): void
    {
        Log::debug($message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param  string  $message  The message to log
     * @param  array<string, mixed>  $context  Additional context data
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * Log an error message.
     *
     * @param  string  $message  The message to log
     * @param  array<string, mixed>  $context  Additional context data
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    /**
     * Log a critical message.
     *
     * @param  string  $message  The message to log
     * @param  array<string, mixed>  $context  Additional context data
     */
    protected function logCritical(string $message, array $context = []): void
    {
        Log::critical($message, $context);
    }
}
