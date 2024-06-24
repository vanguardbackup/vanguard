<?php

declare(strict_types=1);

namespace App\Livewire\Tags;

use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

class DeleteTagButton extends Component
{
    public Tag $tag;

    public function mount(Tag $tag): void
    {
        $this->tag = $tag;
    }

    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->tag);

        Toaster::success("The tag {$this->tag->label} has been removed.");

        $this->tag->forceDelete();

        return Redirect::route('tags.index');
    }

    public function render(): View
    {
        return view('livewire.tags.delete-tag-button');
    }
}
