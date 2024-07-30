<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BackupTaskLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a log entry for a backup task in the system.
 *
 * This model is responsible for storing and managing logs of backup task executions,
 * including their completion and success status.
 */
class BackupTaskLog extends Model
{
    /** @use HasFactory<BackupTaskLogFactory> */
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Scope a query to only include finished backup task logs.
     *
     * @param  Builder<BackupTaskLog>  $builder
     * @return Builder<BackupTaskLog>
     */
    public function scopeFinished(Builder $builder): Builder
    {
        return $builder->whereNotNull('finished_at');
    }

    /**
     * Get the backup task associated with this log entry.
     *
     * @return BelongsTo<BackupTask, BackupTaskLog>
     */
    public function backupTask(): BelongsTo
    {
        return $this->belongsTo(BackupTask::class);
    }

    /**
     * Set the finished time for the backup task log.
     */
    public function setFinishedTime(): void
    {
        $this->updateQuietly(['finished_at' => now()]);
        $this->saveQuietly();
    }

    /**
     * Set the successful time for the backup task log.
     */
    public function setSuccessfulTime(): void
    {
        $this->updateQuietly(['successful_at' => now()]);
        $this->saveQuietly();
    }
}
