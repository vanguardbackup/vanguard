<?php

declare(strict_types=1);

namespace App\Livewire\Tags;

use App\Models\Tag;
use App\Rules\ValidHexColour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

/**
 * Livewire component for creating a new tag.
 *
 * This component handles the form submission and validation for creating a new tag.
 */
class CreateForm extends Component
{
    /** @var string The label for the new tag. */
    public string $label;

    /** @var string|null The optional description for the new tag. */
    public ?string $description = null;

    /** @var string|null The optional colour identifier for the tag. */
    public ?string $colour = null;

    /**
     * Handle the form submission for creating a new tag.
     *
     * Validates the input, creates a new Tag, and redirects to the index page.
     */
    public function submit(): RedirectResponse|Redirector
    {
        $this->validate([
            'label' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'colour' => ['nullable', 'string', new ValidHexColour],
        ], [
            'label.required' => __('Please enter a label.'),
        ]);

        $tag = Tag::create([
            'user_id' => Auth::id(),
            'label' => $this->label,
            'description' => $this->description ?? null,
            'colour' => $this->colour,
        ]);

        Toaster::success('The tag :label has been added.', ['label' => $tag->label]);

        return Redirect::route('tags.index');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.tags.create-form');
    }
}
