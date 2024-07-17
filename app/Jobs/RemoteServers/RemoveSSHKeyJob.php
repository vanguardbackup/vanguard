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
     * @param  RemoveSSHKeyService  $removeSSHKeyService  The service to remove the SSH key
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
