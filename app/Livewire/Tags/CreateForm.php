<?php

declare(strict_types=1);

namespace App\Livewire\Tags;

use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

class CreateForm extends Component
{
    public string $label;
    public ?string $description;

    public function submit(): RedirectResponse|Redirector
    {
        $this->validate([
            'label' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ], [
            'label.required' => __('Please enter a label.'),
        ]);

        $tag = Tag::create([
            'user_id' => Auth::id(),
            'label' => $this->label,
            'description' => $this->description ?? null,
        ]);

        Toaster::success(__('The tag :label has been added.', ['label' => $tag->label]));

        return Redirect::route('tags.index');
    }

    public function render(): View
    {
        return view('livewire.tags.create-form');
    }
}
