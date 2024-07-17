<?php

declare(strict_types=1);

namespace App\Livewire\NotificationStreams\Buttons;

use App\Models\NotificationStream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

class RemoveNotificationStream extends Component
{
    public NotificationStream $notificationStream;

    public function mount(NotificationStream $notificationStream): void
    {
        $this->notificationStream = $notificationStream;
    }

    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->notificationStream);

        $this->notificationStream->forceDelete();

        Toaster::success(__('Notification stream has been removed.'));

        return Redirect::route('notification-streams.index');
    }

    public function render(): View
    {
        return view('livewire.notification-streams.buttons.remove-notification-stream');
    }
}
