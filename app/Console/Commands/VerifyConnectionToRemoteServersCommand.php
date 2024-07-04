<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CheckRemoteServerConnectionJob;
use App\Models\RemoteServer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class VerifyConnectionToRemoteServersCommand extends Command
{
    protected $signature = 'vanguard:verify-connection-to-remote-servers';

    protected $description = 'Verifies if Vanguard can establish connections with remote servers.';

    public function handle(): void
    {
        $this->components->info('Batch job dispatched to check remote server connection');

        $remoteServers = RemoteServer::all();

        if ($remoteServers->isEmpty()) {
            $this->info('No remote servers found. Exiting...');

            return;
        }

        Bus::batch(
            $remoteServers->map(function (RemoteServer $remoteServer): CheckRemoteServerConnectionJob {
                return new CheckRemoteServerConnectionJob($remoteServer->getAttribute('id'));
            })->toArray()
        )->name('Check connection to remote servers')
            ->onQueue('connectivity-checks')
            ->dispatch();
    }
}
