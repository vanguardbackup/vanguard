<?php

declare(strict_types=1);

namespace App\Livewire\Other;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class GenerateSSHKeysButton extends Component
{
    public function generateKeys(): void
    {
        if (! $this->checkAdmin()) {
            return;
        }

        // All the key generation is handled in this command.
        // We're providing a button as a wrapper to help.
        Artisan::call('vanguard:generate-ssh-key');

        Log::info('SSH key generation initiated via button.', ['user_id' => Auth::id()]);

        Toaster::success(__('SSH key generation started. Please reload the page.'));
    }

    public function render(): View
    {
        return view('livewire.other.generate-ssh-keys-button');
    }

    private function checkAdmin(): bool
    {
        if (! Auth::user()?->isAdmin()) {
            Toaster::error(__('You do not have permission to perform this action.'));
            Log::error('Non-admin user attempted to generate SSH keys.', ['user_id' => Auth::id()]);

            return false;
        }

        return true;
    }
}
