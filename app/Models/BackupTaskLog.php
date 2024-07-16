<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BackupTaskLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupTaskLog extends Model
{
    /** @use HasFactory<BackupTaskLogFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @param  Builder<BackupTaskLog>  $builder
     * @return Builder<BackupTaskLog>
     */
    public function scopeFinished(Builder $builder): Builder
    {
        return $builder->whereNotNull('finished_at');
    }

    /**
     * @return BelongsTo<BackupTask, BackupTaskLog>
     */
    public function backupTask(): BelongsTo
    {
        return $this->belongsTo(BackupTask::class);
    }

    public function setFinishedTime(): void
    {
        $this->updateQuietly(['finished_at' => now()]);
        $this->saveQuietly();
    }

    public function setSuccessfulTime(): void
    {
        $this->updateQuietly(['successful_at' => now()]);
        $this->saveQuietly();
    }
}
