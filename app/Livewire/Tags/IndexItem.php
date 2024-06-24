<?php

declare(strict_types=1);

namespace App\Livewire\Tags;

use App\Models\Tag;
use Illuminate\View\View;
use Livewire\Component;

class IndexItem extends Component
{
    public Tag $tag;

    public function mount(Tag $tag): void
    {
        $this->tag = $tag;
    }

    public function render(): View
    {
        return view('livewire.tags.index-item');
    }
}
