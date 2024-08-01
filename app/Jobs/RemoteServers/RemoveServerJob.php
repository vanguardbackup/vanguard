<?php

declare(strict_types=1);

namespace App\Jobs\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to remove a remote server from the system.
 *
 * This job is responsible for forcefully deleting a RemoteServer
 * instance and logging the action.
 */
class RemoveServerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  RemoteServer  $remoteServer  The remote server to be removed
     */
    public function __construct(public RemoteServer $remoteServer)
    {
        //
    }

    /**
     * Execute the job.
     *
     * Logs the removal action and forcefully deletes the remote server.
     */
    public function handle(): void
    {
        Log::info('Removing server.', ['server_id' => $this->remoteServer->getAttribute('id')]);
        $this->remoteServer->forceDelete();
    }
}
