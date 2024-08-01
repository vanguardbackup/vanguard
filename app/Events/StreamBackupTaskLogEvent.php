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
use Illuminate\Support\Facades\Log;

/**
 * Event for streaming backup task log outputs.
 *
 * This event is dispatched to broadcast new log outputs for a backup task.
 * It streams the log output to a private channel specific to the backup task.
 */
class StreamBackupTaskLogEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public BackupTaskLog $backupTaskLog;
    public BackupTask $backupTask;
    public string $logOutput;

    /**
     * Create a new event instance.
     *
     * @param  BackupTaskLog  $backupTaskLog  The backup task log entry
     * @param  string  $logOutput  The new log output to be streamed
     */
    public function __construct(BackupTaskLog $backupTaskLog, string $logOutput)
    {
        Log::debug('StreamBackupTaskLogEvent constructor', ['backupTaskLog' => $backupTaskLog, 'logOutput' => $logOutput]);
        $this->backupTaskLog = $backupTaskLog;
        $this->backupTask = $backupTaskLog->getAttribute('backupTask');
        $this->logOutput = $logOutput;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('backup-task-log.' . $this->backupTask->getAttribute('id')),
        ];
    }
}
