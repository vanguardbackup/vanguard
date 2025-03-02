<?php

declare(strict_types=1);

namespace App\Livewire\Scripts\Tables;

use App\Models\Script;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Livewire component for displaying a paginated table of scripts.
 *
 * This component handles the retrieval and rendering of scripts for the current user.
 */
class IndexTable extends Component
{
    use WithPagination;

    /**
     * Render the component.
     *
     * Retrieves a paginated list of scripts for the authenticated user
     * and passes them to the view for rendering.
     */
    public function render(): View
    {
        $scripts = Script::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(Auth::user()?->getAttribute('pagination_count') ?? 15, pageName: 'scripts');

        return view('livewire.scripts.tables.index-table', ['scripts' => $scripts]);
    }
}
