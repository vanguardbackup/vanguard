<?php

namespace App\Traits;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTags
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables', 'taggable_id', 'tag_id');
    }

    public function tag($label): void
    {
        $tag = Tag::firstOrCreate(['label' => $label])
            ->where('user_id', auth()->id());

        $this->tags()->syncWithoutDetaching($tag);
    }

    public function untag($label): void
    {
        $tag = Tag::where('label', $label)
            ->first();

        if ($tag) {
            $this->tags()->detach($tag);
        }
    }
}
