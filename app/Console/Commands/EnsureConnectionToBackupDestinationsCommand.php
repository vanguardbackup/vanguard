<?php

namespace App\Console\Commands;

use App\Jobs\CheckBackupDestinationsS3ConnectionJob;
use App\Models\BackupDestination;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class EnsureConnectionToBackupDestinationsCommand extends Command
{
    protected $signature = 'vanguard:ensure-connection-to-backup-destinations';

    protected $description = 'Ensure connectivity to available backup destinations.';

    public function handle(): void
    {
        $this->info('Checking connection to eligible backup destinations...');

        $backupDestinations = BackupDestination::all();

        if ($backupDestinations->isEmpty()) {
            $this->info('No backup destinations found.');

            return;
        }

        $jobs = $backupDestinations->map(function (BackupDestination $backupDestination) {
            if ($backupDestination->isS3Connection()) {
                $this->info("Dispatching job for backup destination ID: {$backupDestination->id}");

                return new CheckBackupDestinationsS3ConnectionJob($backupDestination);
            }

            return null;
        })->filter()->toArray();

        if (empty($jobs)) {
            $this->info('No eligible backup destinations found.');

            return;
        }

        Bus::batch($jobs)
            ->name('Check connection to eligible backup destinations')
            ->onQueue('connectivity-checks')
            ->dispatch();
    }
}
