<?php

declare(strict_types=1);

namespace App\Livewire\Tags;

use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Livewire component for displaying a paginated table of tags.
 *
 * This component handles the retrieval and rendering of tags for the current user.
 */
class IndexTable extends Component
{
    use WithPagination;

    /**
     * Render the component.
     *
     * Retrieves a paginated list of tags for the authenticated user
     * and passes them to the view for rendering.
     */
    public function render(): View
    {
        $tags = Tag::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(Auth::user()?->getAttribute('pagination_count') ?? 15, pageName: 'tags');

        return view('livewire.tags.index-table', ['tags' => $tags]);
    }
}
