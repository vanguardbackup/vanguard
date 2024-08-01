<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CheckRemoteServerConnectionJob;
use App\Models\RemoteServer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

/**
 * Command to verify connections to all registered remote servers.
 *
 * This command dispatches a batch job to check the connectivity
 * of all remote servers registered in the system.
 */
class VerifyConnectionToRemoteServersCommand extends Command
{
    protected $signature = 'vanguard:verify-connection-to-remote-servers';

    protected $description = 'Verifies if Vanguard can establish connections with remote servers.';

    /**
     * Execute the console command.
     *
     * This method retrieves all remote servers, creates a job for each server
     * to check its connection, and dispatches these jobs as a batch on a
     * specific queue. If no remote servers are found, it exits early.
     */
    public function handle(): void
    {
        $this->components->info('Batch job dispatched to check remote server connection');

        $remoteServers = RemoteServer::all();

        if ($remoteServers->isEmpty()) {
            $this->info('No remote servers found. Exiting...');

            return;
        }

        Bus::batch(
            $remoteServers->map(fn (RemoteServer $remoteServer): CheckRemoteServerConnectionJob => new CheckRemoteServerConnectionJob($remoteServer->getAttribute('id')))->toArray()
        )->name('Check connection to remote servers')
            ->onQueue('connectivity-checks')
            ->dispatch();
    }
}
