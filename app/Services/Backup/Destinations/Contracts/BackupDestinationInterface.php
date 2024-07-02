<?php

declare(strict_types=1);

namespace App\Services\Backup\BackupDestinations\Contracts;

use app\Services\Backup\Contracts\SFTPInterface;

interface BackupDestinationInterface
{
    /**
     * List files matching the given pattern.
     *
     * @return array<string>
     */
    public function listFiles(string $pattern): array;

    public function deleteFile(string $filePath): void;

    public function streamFiles(SFTPInterface $sftp, string $remoteZipPath, string $fileName, string $storagePath, int $retries = 3, int $delay = 5): bool;
}
