<?php

declare(strict_types=1);

namespace App\Livewire\Tags;

use App\Models\Tag;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Livewire component for displaying a single tag item in a list.
 *
 * This component handles the rendering of an individual tag in the tags index.
 */
class IndexItem extends Component
{
    /** @var Tag The tag instance to be displayed. */
    public Tag $tag;

    /**
     * Initialize the component with the given tag.
     */
    public function mount(Tag $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.tags.index-item');
    }
}
