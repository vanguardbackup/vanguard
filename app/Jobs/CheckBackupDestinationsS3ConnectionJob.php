<?php

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
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public BackupDestination $backupDestination)
    {
        //
    }

    public function handle(): void
    {
        $action = new CheckS3Connection;
        $action->handle($this->backupDestination);
    }
}
