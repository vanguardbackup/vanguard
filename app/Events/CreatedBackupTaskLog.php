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

class CreatedBackupTaskLog implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;
    public BackupTask $backupTask;

    public function __construct(public BackupTaskLog $backupTaskLog)
    {
        $this->backupTask = $this->backupTaskLog->getAttribute('backupTask');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
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
