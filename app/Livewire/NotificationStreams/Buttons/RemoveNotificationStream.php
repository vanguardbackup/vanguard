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

/**
 * Manages the button for removing a notification stream.
 *
 * This component handles the UI and logic for deleting a specific notification stream.
 */
class RemoveNotificationStream extends Component
{
    /** @var NotificationStream The notification stream to be removed */
    public NotificationStream $notificationStream;

    /**
     * Initialize the component with a notification stream.
     */
    public function mount(NotificationStream $notificationStream): void
    {
        $this->notificationStream = $notificationStream;
    }

    /**
     * Delete the notification stream.
     *
     * Authorizes the action, deletes the stream, and redirects to the index page.
     */
    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('forceDelete', $this->notificationStream);

        $this->notificationStream->forceDelete();

        Toaster::success('Notification stream has been removed.');

        return Redirect::route('notification-streams.index');
    }

    /**
     * Render the remove notification stream button.
     */
    public function render(): View
    {
        return view('livewire.notification-streams.buttons.remove-notification-stream');
    }
}
