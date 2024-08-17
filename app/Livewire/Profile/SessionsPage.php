<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Manages the user's sessions page in the profile section.
 * Renders the view using the 'account-app' layout.
 */
class SessionsPage extends Component
{
    public function render(): View
    {
        return view('livewire.profile.sessions-page')
            ->layout('components.layouts.account-app');
    }
}
