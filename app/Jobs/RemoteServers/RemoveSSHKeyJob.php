<?php

declare(strict_types=1);

namespace App\Jobs\RemoteServers;

use App\Models\RemoteServer;
use App\Services\RemoveSSHKey\RemoveSSHKeyService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to remove an SSH key from a remote server.
 *
 * This job is responsible for initiating the removal of an SSH key
 * from a specified remote server using the RemoveSSHKeyService.
 */
class RemoveSSHKeyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  RemoteServer  $remoteServer  The remote server to remove the SSH key from
     */
    public function __construct(public RemoteServer $remoteServer) {}

    /**
     * Execute the job.
     *
     * Attempts to remove the SSH key from the remote server and logs the outcome.
     * If an exception occurs, it's logged and re-thrown.
     *
     * @param  RemoveSSHKeyService  $removeSSHKeyService  The service to remove the SSH key
     *
     * @throws Exception If the SSH key removal fails
     */
    public function handle(RemoveSSHKeyService $removeSSHKeyService): void
    {
        Log::info('Starting SSH key removal job.', ['server_id' => $this->remoteServer->getAttribute('id')]);

        try {
            $removeSSHKeyService->handle($this->remoteServer);
            Log::info('SSH key removal job completed successfully.', ['server_id' => $this->remoteServer->getAttribute('id')]);
        } catch (Exception $e) {
            Log::error('SSH key removal job failed.', [
                'server_id' => $this->remoteServer->getAttribute('id'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
