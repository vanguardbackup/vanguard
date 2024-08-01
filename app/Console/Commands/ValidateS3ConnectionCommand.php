<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\BackupDestinations\CheckS3Connection;
use App\Models\BackupDestination;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command to validate the connection to a specific S3 backup destination.
 *
 * This command tests the connectivity to an S3 bucket specified by its ID.
 * It ensures the backup destination exists and is an S3 connection before
 * attempting to validate the connection.
 */
class ValidateS3ConnectionCommand extends Command
{
    protected $signature = 'vanguard:validate-s3-connection {id}';

    protected $description = 'Validates connectivity to the specified S3 bucket.';

    /**
     * Create a new command instance.
     *
     * @param  CheckS3Connection  $checkS3Connection  The action to check S3 connections
     */
    public function __construct(protected CheckS3Connection $checkS3Connection)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * This method retrieves the backup destination by ID, verifies it's an S3 connection,
     * then attempts to validate the connection. The result is logged and displayed to the user.
     */
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

        $response = $this->checkS3Connection->handle($backupDestination);

        if (! $response) {
            Log::debug('Connection failed.');
            $this->components->error('Connection failed.');

            return;
        }

        Log::debug('Connection successful.');
        $this->components->info('Connection successful.');
    }
}
