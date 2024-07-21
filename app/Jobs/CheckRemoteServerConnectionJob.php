<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\RemoteServer\CheckRemoteServerConnection;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to check the connection status of a remote server
 *
 * This job is responsible for initiating a connection check to a specific remote server.
 * It can be dispatched to the queue for asynchronous processing.
 */
class CheckRemoteServerConnectionJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance
     *
     * @param  int  $remoteServerId  The ID of the remote server to check
     */
    public function __construct(public readonly int $remoteServerId) {}

    /**
     * Execute the job
     *
     * Performs the connection check for the specified remote server and logs the result.
     *
     * @param  CheckRemoteServerConnection  $checkRemoteServerConnection  The action to perform the connection check
     *
     * @throws Exception
     */
    public function handle(CheckRemoteServerConnection $checkRemoteServerConnection): void
    {
        $result = $checkRemoteServerConnection->byRemoteServerId($this->remoteServerId);

        Log::info('[Server Connection Check Job] Completed', [
            'server_id' => $this->remoteServerId,
            'result' => $result,
        ]);
    }
}
