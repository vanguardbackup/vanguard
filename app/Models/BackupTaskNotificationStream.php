<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BackupTaskNotificationStream extends Pivot
{
    /**
     * @return BelongsTo<BackupTask, \App\Models\BackupTaskNotificationStream>
     */
    public function backupTask(): BelongsTo
    {
        return $this->belongsTo(BackupTask::class);
    }

    /**
     * @return BelongsTo<NotificationStream, \App\Models\BackupTaskNotificationStream>
     */
    public function notificationStream(): BelongsTo
    {
        return $this->belongsTo(NotificationStream::class);
    }
}
