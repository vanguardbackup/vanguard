<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Class HelpPage
 *
 * This Livewire component manages the Help page in the user's profile section.
 * It provides access to various support resources and assistance options.
 */
class HelpPage extends Component
{
    /**
     * Render the Help page component.
     *
     * This method returns the view for the Help page, using the 'account-app' layout.
     * The page displays support resources, documentation links, and contact information.
     *
     * @return View The rendered view for the Help page
     */
    public function render(): View
    {
        return view('livewire.profile.help-page')
            ->layout('components.layouts.account-app');
    }
}
