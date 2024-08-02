<?php

declare(strict_types=1);

namespace App\Livewire\Other;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Livewire component for generating SSH keys.
 *
 * This component provides functionality to generate SSH keys
 * via an Artisan command, restricted to admin users only.
 */
class GenerateSSHKeysButton extends Component
{
    /**
     * Generate SSH keys.
     *
     * This method checks for admin privileges, runs the key generation
     * Artisan command, logs the action, and displays a success message.
     */
    public function generateKeys(): void
    {
        if (! $this->checkAdmin()) {
            return;
        }

        // All the key generation is handled in this command.
        // We're providing a button as a wrapper to help.
        Artisan::call('vanguard:generate-ssh-key');

        Log::info('SSH key generation initiated via button.', ['user_id' => Auth::id()]);

        Toaster::success('SSH key generation started. Please reload the page.');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.other.generate-ssh-keys-button');
    }

    /**
     * Check if the current user has admin privileges.
     *
     * @return bool Returns true if the user is an admin, false otherwise.
     */
    private function checkAdmin(): bool
    {
        if (! Auth::user()?->isAdmin()) {
            Toaster::error('You do not have permission to perform this action.');
            Log::error('Non-admin user attempted to generate SSH keys.', ['user_id' => Auth::id()]);

            return false;
        }

        return true;
    }
}
