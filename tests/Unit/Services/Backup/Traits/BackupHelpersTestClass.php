<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Backup\Traits;

use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Traits\BackupHelpers;

class BackupHelpersTestClass
{
    use BackupHelpers;

    public function publicDownloadFileViaSFTP(SFTPInterface $sftp, string $remoteZipPath): string
    {
        return $this->downloadFileViaSFTP($sftp, $remoteZipPath);
    }

    public function publicOpenFileAsStream(string $tempFile): mixed
    {
        return $this->openFileAsStream($tempFile);
    }

    public function publicCleanUpTempFile(string $tempFile): void
    {
        $this->cleanUpTempFile($tempFile);
    }

    public function publicLogInfo(string $message, array $context = []): void
    {
        $this->logInfo($message, $context);
    }

    public function publicLogDebug(string $message, array $context = []): void
    {
        $this->logDebug($message, $context);
    }

    public function publicLogWarning(string $message, array $context = []): void
    {
        $this->logWarning($message, $context);
    }

    public function publicLogError(string $message, array $context = []): void
    {
        $this->logError($message, $context);
    }

    public function publicLogCritical(string $message, array $context = []): void
    {
        $this->logCritical($message, $context);
    }
}
