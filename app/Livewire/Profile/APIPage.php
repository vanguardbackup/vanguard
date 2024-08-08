<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Class APIPage
 *
 * This Livewire component represents the API page in the user's profile section.
 * It is responsible for rendering the API page view with the appropriate layout.
 */
class APIPage extends Component
{
    /**
     * Render the API page component.
     *
     * This method returns the view for the API page, using the 'account-app' layout.
     *
     * @return View The rendered view for the API page
     */
    public function render(): View
    {
        return view('livewire.profile.api-page')
            ->layout('components.layouts.account-app');
    }
}
