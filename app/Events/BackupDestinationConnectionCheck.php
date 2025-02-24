<?php

declare(strict_types=1);

namespace App\Events;

use Override;
use App\Models\BackupDestination;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event for broadcasting backup destination connection check status.
 *
 * This event is dispatched when a connection check is performed on a backup destination.
 * It broadcasts the status of the connection check to a private channel specific to the backup destination.
 */
class BackupDestinationConnectionCheck implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  BackupDestination  $backupDestination  The backup destination being checked
     * @param  string|null  $status  The status of the connection check. If null, it uses the backup destination's current status.
     */
    public function __construct(
        private readonly BackupDestination $backupDestination,
        public ?string $status = null
    ) {
        $this->status = $status ?? $this->backupDestination->getAttribute('status');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    #[Override]
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('backup-destinations.' . $this->backupDestination->getAttribute('id')),
        ];
    }
}
