<?php

declare(strict_types=1);

namespace App\Services\Backup\Traits;

use App\Services\Backup\Contracts\SFTPInterface;
use Exception;
use Illuminate\Support\Facades\Log;

trait BackupHelpers
{
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

    protected function cleanUpTempFile(string $tempFile): void
    {
        if (file_exists($tempFile)) {
            unlink($tempFile);
            Log::debug('Temporary file deleted.', ['temp_file' => $tempFile]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logDebug(string $message, array $context = []): void
    {
        Log::debug($message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logCritical(string $message, array $context = []): void
    {
        Log::critical($message, $context);
    }
}
