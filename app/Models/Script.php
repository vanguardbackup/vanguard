<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ScriptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;

class Script extends Model
{
    /** @use HasFactory<ScriptFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * Script type constants.
     */
    public const string TYPE_PRESCRIPT = 'prescript';
    public const string TYPE_POSTSCRIPT = 'postscript';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = ['id'];

    /**
     * Get the backup tasks associated with this script.
     *
     * @return BelongsToMany<BackupTask, $this>
     */
    public function backupTasks(): BelongsToMany
    {
        return $this->belongsToMany(BackupTask::class, 'backup_task_script');
    }

    /**
     * Scope to get only prescript type scripts.
     *
     * @param  Builder<Script>  $builder
     * @return Builder<Script>
     */
    public function scopePrebackupScripts(Builder $builder): Builder
    {
        return $builder->where('type', self::TYPE_PRESCRIPT);
    }

    /**
     * Scope to get only postscript type scripts.
     *
     * @param  Builder<Script>  $builder
     * @return Builder<Script>
     */
    public function scopePostbackupScripts(Builder $builder): Builder
    {
        return $builder->where('type', self::TYPE_POSTSCRIPT);
    }

    /**
     * Returns whether the last execution of the script was successful or not.
     */
    public function wasSucessful(): bool
    {
        return $this->getAttribute('successful_at') !== null;
    }

    /**
     *  Marks the script as successful.
     */
    public function markAsSuccessful(): void
    {
        $this->update(['successful_at' => now()]);
    }

    /**
     *  Marks the script as unsuccessful.
     */
    public function markAsUnsuccessful(): void
    {
        $this->update(['successful_at' => null]);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
