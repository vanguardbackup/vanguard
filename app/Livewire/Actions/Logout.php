<?php

declare(strict_types=1);

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Handles the user logout process.
 *
 * This class is responsible for logging out the user and invalidating their session.
 */
class Logout
{
    /**
     * Execute the logout action.
     *
     * Logs out the user, invalidates the session, and regenerates the CSRF token.
     */
    public function __invoke(): void
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();
    }
}
