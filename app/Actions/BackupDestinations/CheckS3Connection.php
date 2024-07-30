<?php

declare(strict_types=1);

namespace App\Actions\BackupDestinations;

use App\Events\BackupDestinationConnectionCheck;
use App\Models\BackupDestination;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Checks the connection to an S3 backup destination.
 *
 * This action verifies the reachability of an S3 backup destination
 * and updates its status accordingly.
 */
class CheckS3Connection
{
    /**
     * Attempt to connect to the S3 backup destination.
     *
     * @param  BackupDestination  $backupDestination  The backup destination to check
     * @return bool True if connection is successful, false otherwise
     */
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
