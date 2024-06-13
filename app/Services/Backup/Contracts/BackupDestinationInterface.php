<?php

namespace App\Services\Backup\Contracts;

interface BackupDestinationInterface
{
    public function listFiles(string $pattern): array;

    public function deleteFile(string $filePath): void;
}
