<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Taggable extends Model
{
    use HasFactory;

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
