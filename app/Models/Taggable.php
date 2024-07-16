<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Taggable extends Model
{
    protected $guarded = [];

    /**
     * @return BelongsTo<Tag, Taggable>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * @return MorphTo<Model, Taggable>
     */
    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}
