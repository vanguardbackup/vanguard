<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Handles the experiments page in the user's profile section.
 */
class ExperimentsPage extends Component
{
    /**
     * Render the experiments page view.
     */
    public function render(): View
    {
        return view('livewire.profile.experiments-page')
            ->layout('components.layouts.account-app');
    }
}
