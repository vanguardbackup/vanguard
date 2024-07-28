<?php

declare(strict_types=1);

namespace App\Livewire\NotificationStreams;

use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(): View
    {
        return view('livewire.notification-streams.index')
            ->layout('components.layouts.account-app');
    }
}
