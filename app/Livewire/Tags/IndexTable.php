<?php

declare(strict_types=1);

namespace App\Livewire\Tags;

use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class IndexTable extends Component
{
    use withPagination;

    public function render(): View
    {
        $tags = Tag::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(30, pageName: 'tags');

        return view('livewire.tags.index-table', ['tags' => $tags]);
    }
}
