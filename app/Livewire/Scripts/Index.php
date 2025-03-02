<?php

declare(strict_types=1);

namespace App\Livewire\Scripts;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Manages the index view for scripts.
 *
 * This Livewire component handles the display of the scripts index page.
 */
class Index extends Component
{
    /**
     * Render the notification streams index view.
     */
    public function render(): View
    {
        return view('livewire.scripts.index')
            ->layout('components.layouts.account-app');
    }
}
