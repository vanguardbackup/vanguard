<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\RemoteServer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoteServerConnectivityStatusChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly RemoteServer $remoteServer,
        public ?string $connectivityStatus = null
    ) {
        $this->connectivityStatus = $connectivityStatus ?? $remoteServer->getAttribute('connectivity_status');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('remote-servers.' . $this->remoteServer->getAttribute('id')),
        ];
    }
}
