<?php

declare(strict_types=1);

namespace App\Livewire\NotificationStreams;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Manages the index view for notification streams.
 *
 * This Livewire component handles the display of the notification streams index page.
 */
class Index extends Component
{
    /**
     * Render the notification streams index view.
     */
    public function render(): View
    {
        return view('livewire.notification-streams.index')
            ->layout('components.layouts.account-app');
    }
}
