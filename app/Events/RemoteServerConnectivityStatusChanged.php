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
use Override;

/**
 * Event for broadcasting changes in remote server connectivity status.
 *
 * This event is dispatched when the connectivity status of a remote server changes.
 * It broadcasts the new status to a private channel specific to the remote server.
 */
class RemoteServerConnectivityStatusChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  RemoteServer  $remoteServer  The remote server whose connectivity status has changed
     * @param  string|null  $connectivityStatus  The new connectivity status. If null, it uses the remote server's current status.
     */
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
    #[Override]
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('remote-servers.' . $this->remoteServer->getAttribute('id')),
        ];
    }
}
