<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents additional data associated with a backup task.
 *
 * This model is designed to store flexible, task-specific data that doesn't fit
 * into the main BackupTask model structure.
 */
class BackupTaskData extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
}
