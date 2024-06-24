<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\BackupDestinations\CheckS3Connection;
use App\Models\BackupDestination;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ValidateS3ConnectionCommand extends Command
{
    protected $signature = 'vanguard:validate-s3-connection {id}';

    protected $description = 'Validates connectivity to the specified S3 bucket.';

    protected CheckS3Connection $checkConnection;

    public function __construct(CheckS3Connection $checkConnection)
    {
        parent::__construct();

        $this->checkConnection = $checkConnection;
    }

    public function handle(): void
    {
        $id = $this->argument('id');

        $backupDestination = BackupDestination::find($id);

        if (! $backupDestination) {
            $this->components->error('The backup destination does not exist.');

            return;
        }

        if (! $backupDestination->isS3Connection()) {
            $this->components->error('Backup destination is not an S3 connection.');

            return;
        }

        $response = $this->checkConnection->handle($backupDestination);

        if (! $response) {
            Log::debug('Connection failed.');
            $this->components->error('Connection failed.');

            return;
        }

        Log::debug('Connection successful.');
        $this->components->info('Connection successful.');
    }
}
