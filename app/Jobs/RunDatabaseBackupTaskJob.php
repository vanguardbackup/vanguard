<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Backup\Tasks\DatabaseBackupTask;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to run a database backup task.
 *
 * This job is queued and executes a DatabaseBackupTask for a specific backup task ID.
 * It has a timeout of 30 minutes to allow for potentially long-running backup operations.
 */
class RunDatabaseBackupTaskJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1800; // 30 minutes

    /**
     * Create a new job instance.
     *
     * @param  int  $backupTaskId  The ID of the backup task to run.
     */
    public function __construct(public int $backupTaskId)
    {
        //
    }

    /**
     * Execute the job.
     *
     * This method creates a new instance of DatabaseBackupTask with the provided
     * backup task ID and calls its handle method to perform the backup.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $databaseBackupTask = new DatabaseBackupTask($this->backupTaskId);
        $databaseBackupTask->handle();
    }
}
