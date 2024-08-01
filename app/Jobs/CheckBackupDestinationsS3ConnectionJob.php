<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\BackupDestinations\CheckS3Connection;
use App\Models\BackupDestination;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to check the S3 connection of a backup destination.
 *
 * This job is queued and can be batched. It uses the CheckS3Connection action
 * to verify the connection to the S3 bucket associated with a backup destination.
 */
class CheckBackupDestinationsS3ConnectionJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  BackupDestination  $backupDestination  The backup destination to check.
     */
    public function __construct(public BackupDestination $backupDestination)
    {
        //
    }

    /**
     * Execute the job.
     *
     * This method creates a new instance of CheckS3Connection and calls its handle method
     * with the backup destination provided in the constructor.
     */
    public function handle(): void
    {
        $checkS3Connection = new CheckS3Connection;
        $checkS3Connection->handle($this->backupDestination);
    }
}
