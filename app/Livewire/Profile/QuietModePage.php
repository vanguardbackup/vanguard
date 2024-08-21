<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Class QuietModePage
 *
 * This Livewire component represents the Quiet Mode page in the user's profile section.
 * It is responsible for rendering the Quiet Mode page view with the appropriate layout.
 * The Quiet Mode feature allows users to temporarily disable notifications and updates.
 */
class QuietModePage extends Component
{
    /**
     * Render the Quiet Mode page component.
     *
     * This method returns the view for the Quiet Mode page, using the 'account-app' layout.
     * The view contains controls for managing the user's Quiet Mode settings.
     *
     * @return View The rendered view for the Quiet Mode page
     */
    public function render(): View
    {
        return view('livewire.profile.quiet-mode-page')
            ->layout('components.layouts.account-app');
    }
}
