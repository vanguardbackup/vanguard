<?php

declare(strict_types=1);

namespace App\Livewire\Scripts;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Manages the create view for scripts.
 *
 * This Livewire component handles the display of the scripts create page.
 */
class Create extends Component
{
    /**
     * Render the scripts index view.
     */
    public function render(): View
    {
        return view('livewire.scripts.create')
            ->layout('components.layouts.account-app');
    }
}
