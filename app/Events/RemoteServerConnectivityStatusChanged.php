<?php

namespace App\Events;

use App\Models\RemoteServer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoteServerConnectivityStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly RemoteServer $remoteServer,
        public ?string $connectivityStatus = null
    ) {
        $this->connectivityStatus = $connectivityStatus ?? $remoteServer->connectivity_status;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("remote-servers.{$this->remoteServer->id}"),
        ];
    }
}
