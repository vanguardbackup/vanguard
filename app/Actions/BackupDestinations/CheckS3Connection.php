<?php

declare(strict_types=1);

namespace App\Actions\BackupDestinations;

use App\Events\BackupDestinationConnectionCheck;
use App\Models\BackupDestination;
use Exception;
use Illuminate\Support\Facades\Log;

class CheckS3Connection
{
    public function handle(BackupDestination $backupDestination): bool
    {
        if (! $backupDestination->isS3Connection()) {
            Log::info('[S3] Backup destination is not an S3 connection. Skipping.');

            return false;
        }

        $s3Client = $backupDestination->getS3Client();

        try {
            $s3Client->listBuckets();

            BackupDestinationConnectionCheck::dispatch($backupDestination, BackupDestination::STATUS_REACHABLE);
            $backupDestination->markAsReachable();

            return true;
        } catch (Exception $exception) {
            Log::error('[S3] Failed to list buckets: ' . $exception->getMessage());
            BackupDestinationConnectionCheck::dispatch($backupDestination, BackupDestination::STATUS_UNREACHABLE);
            $backupDestination->markAsUnreachable();

            return false;
        }
    }
}
