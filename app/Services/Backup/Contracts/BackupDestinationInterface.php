<?php

namespace App\Services\Backup\Contracts;

use phpseclib3\Net\SFTP;

interface BackupDestinationInterface
{
    public function listFiles(string $pattern): array;

    public function deleteFile(string $filePath): void;

    public function streamFiles(SFTP $sftp, string $remoteZipPath, string $fileName, string $storagePath, int $retries = 3, int $delay = 5): bool;
}
