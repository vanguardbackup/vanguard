<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\View\View;
use Livewire\Component;

/**
 * MFAPage component for managing Multi-Factor Authentication settings.
 *
 * This component renders the MFA management page within the user's profile section,
 * allowing users to configure their MFA preferences.
 */
class MFAPage extends Component
{
    /**
     * Render the MFA management page.
     */
    public function render(): View
    {
        return view('livewire.profile.mfa-page')
            ->layout('components.layouts.account-app');
    }
}
