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

/**
 * Livewire component for deleting a tag.
 *
 * This component provides functionality to delete a tag
 * and handle the associated user interface interactions.
 */
class DeleteTagButton extends Component
{
    /** @var Tag The tag instance to be deleted. */
    public Tag $tag;

    /**
     * Initialize the component with the given tag.
     */
    public function mount(Tag $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * Delete the tag.
     *
     * This method authorizes the action, deletes the tag,
     * shows a success message, and redirects to the tags index page.
     */
    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->tag);

        Toaster::success('The tag has been removed.');

        $this->tag->forceDelete();

        return Redirect::route('tags.index');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.tags.delete-tag-button');
    }
}
