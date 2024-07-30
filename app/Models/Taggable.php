<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Represents a taggable relationship in the system.
 *
 * This model serves as a pivot between tags and the various models that can be tagged,
 * implementing a polymorphic many-to-many relationship.
 */
class Taggable extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * Get the tag associated with this taggable relationship.
     *
     * @return BelongsTo<Tag, Taggable>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * Get the parent taggable model (the model being tagged).
     *
     * @return MorphTo<Model, Taggable>
     */
    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}
