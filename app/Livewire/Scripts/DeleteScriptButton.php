<?php

declare(strict_types=1);

namespace App\Livewire\Scripts;

use App\Models\Script;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

/**
 * Livewire component for deleting a script.
 *
 * This component provides functionality to delete a script
 * and handle the associated user interface interactions.
 */
class DeleteScriptButton extends Component
{
    /** @var Script The script instance to be deleted. */
    public Script $script;

    /**
     * Initialize the component with the given script.
     */
    public function mount(Script $script): void
    {
        $this->script = $script;
    }

    /**
     * Delete the script.
     *
     * This method authorizes the action, deletes the script,
     * shows a success message, and redirects to the scripts index page.
     */
    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->script);

        Toaster::success('The script has been removed.');

        $this->script->forceDelete();

        return Redirect::route('scripts.index');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.scripts.delete-button');
    }
}
