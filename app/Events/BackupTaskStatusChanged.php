<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\BackupTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Override;

/**
 * Event for broadcasting changes in backup task status.
 *
 * This event is dispatched when the status of a backup task changes.
 * It broadcasts the new status to a private channel specific to the backup task.
 */
class BackupTaskStatusChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  BackupTask  $backupTask  The backup task whose status has changed
     * @param  string|null  $status  The new status of the backup task. If null, it uses the backup task's current status.
     */
    public function __construct(
        private readonly BackupTask $backupTask,
        public ?string $status = null
    ) {
        $this->status = $status ?? $backupTask->getAttribute('status');
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
            new PrivateChannel('backup-tasks.' . $this->backupTask->getAttribute('id')),
        ];
    }
}
