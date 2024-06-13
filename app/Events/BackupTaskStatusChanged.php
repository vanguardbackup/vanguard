<?php

namespace App\Events;

use App\Models\BackupTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupTaskStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly BackupTask $backupTask,
        public ?string $status = null
    ) {
        $this->status = $status ?? $backupTask->status;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("backup-tasks.{$this->backupTask->id}"),
        ];
    }
}
