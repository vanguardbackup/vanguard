<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Override;

/**
 * Event for broadcasting the creation of a new backup task log.
 *
 * This event is dispatched when a new log entry is created for a backup task.
 * It broadcasts the ID of the new log entry to a private channel specific to the backup task.
 */
class CreatedBackupTaskLog implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public BackupTask $backupTask;

    /**
     * Create a new event instance.
     *
     * @param  BackupTaskLog  $backupTaskLog  The newly created backup task log entry
     */
    public function __construct(public BackupTaskLog $backupTaskLog)
    {
        $this->backupTask = $this->backupTaskLog->getAttribute('backupTask');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    #[Override]
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('new-backup-task-log.' . $this->backupTask->getAttribute('id')),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return ['logId' => $this->backupTaskLog->getAttribute('id')];
    }
}
