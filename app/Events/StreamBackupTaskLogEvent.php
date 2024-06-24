<?php

namespace App\Events;

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StreamBackupTaskLogEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BackupTaskLog $backupTaskLog;

    public BackupTask $backupTask;

    public string $logOutput;

    public function __construct(BackupTaskLog $backupTaskLog, string $logOutput)
    {
        Log::debug('StreamBackupTaskLogEvent constructor', ['backupTaskLog' => $backupTaskLog, 'logOutput' => $logOutput]);
        $this->backupTaskLog = $backupTaskLog;
        $this->backupTask = $backupTaskLog->backupTask;
        $this->logOutput = $logOutput;

    }

    public function broadcastOn(): array
    {
        return [
            new Channel("backup-task-log.{$this->backupTask->id}"),
        ];
    }
}
