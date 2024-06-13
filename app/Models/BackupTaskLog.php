<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupTaskLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeFinished($query)
    {
        return $query->whereNotNull('finished_at');
    }

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
