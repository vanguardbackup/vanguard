<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\BackupDestination;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupDestinationConnectionCheck implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

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
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('backup-destinations.' . $this->backupDestination->getAttribute('id')),
        ];
    }
}
