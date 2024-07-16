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

class UpdateForm extends Component
{
    public string $label;

    public ?string $description = null;

    public Tag $tag;

    public function mount(Tag $tag): void
    {
        $this->tag = $tag;
        $this->label = $tag->getAttribute('label');
        $this->description = $tag->getAttribute('description') ?? null;
    }

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

        Toaster::success(__('The tag :label has been updated.', ['label' => $this->tag->getAttribute('label')]));

        return Redirect::route('tags.index');
    }

    public function render(): View
    {
        return view('livewire.tags.update-form');
    }
}
