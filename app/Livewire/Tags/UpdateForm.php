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
 * Livewire component for updating an existing tag.
 *
 * This component handles the form submission and validation for updating a tag.
 */
class UpdateForm extends Component
{
    /** @var string The updated label for the tag. */
    public string $label;

    /** @var string|null The updated description for the tag. */
    public ?string $description = null;

    /** @var Tag The tag instance being updated. */
    public Tag $tag;

    /**
     * Initialize the component with the given tag.
     *
     * Populates the component properties with the tag's current attributes.
     */
    public function mount(Tag $tag): void
    {
        $this->tag = $tag;
        $this->label = $tag->getAttribute('label');
        $this->description = $tag->getAttribute('description') ?? null;
    }

    /**
     * Handle the form submission for updating the tag.
     *
     * Validates the input, updates the Tag, and redirects to the index page.
     */
    public function submit(): RedirectResponse|Redirector
    {
        $this->authorize('update', $this->tag);

        $this->validate([
            'label' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ], [
            'label.required' => __('Please enter a label.'),
        ]);

        $this->tag->update([
            'label' => $this->label,
            'description' => $this->description ?? null,
        ]);

        $this->tag->save();

        Toaster::success('The tag :label has been updated.', ['label' => $this->tag->getAttribute('label')]);

        return Redirect::route('tags.index');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.tags.update-form');
    }
}
