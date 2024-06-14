<?php

namespace App\Jobs\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemoveServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public RemoteServer $remoteServer)
    {
        //
    }

    public function handle(): void
    {
        Log::info('Removing server.', ['server_id' => $this->remoteServer->id]);
        $this->remoteServer->forceDelete();
    }
}
