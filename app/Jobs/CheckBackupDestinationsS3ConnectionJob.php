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

class CheckBackupDestinationsS3ConnectionJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public BackupDestination $backupDestination)
    {
        //
    }

    public function handle(): void
    {
        $checkS3Connection = new CheckS3Connection;
        $checkS3Connection->handle($this->backupDestination);
    }
}
