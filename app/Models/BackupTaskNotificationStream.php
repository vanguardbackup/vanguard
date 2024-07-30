<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Represents the pivot relationship between BackupTask and NotificationStream.
 *
 * This model manages the many-to-many relationship between backup tasks
 * and notification streams, allowing for flexible notification configurations.
 */
class BackupTaskNotificationStream extends Pivot
{
    /**
     * Get the backup task associated with this pivot.
     *
     * @return BelongsTo<BackupTask, BackupTaskNotificationStream>
     */
    public function backupTask(): BelongsTo
    {
        return $this->belongsTo(BackupTask::class);
    }

    /**
     * Get the notification stream associated with this pivot.
     *
     * @return BelongsTo<NotificationStream, BackupTaskNotificationStream>
     */
    public function notificationStream(): BelongsTo
    {
        return $this->belongsTo(NotificationStream::class);
    }
}
