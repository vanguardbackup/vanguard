<?php

declare(strict_types=1);

namespace App\Livewire\NotificationStreams\Forms;

use App\Models\NotificationStream;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

/**
 * Manages the form for creating a new notification stream.
 *
 * This component handles the UI and logic for adding a new notification stream,
 * including form validation and submission.
 */
class CreateNotificationStream extends Component
{
    /** @var NotificationStreamForm The form object for creating a notification stream */
    public NotificationStreamForm $form;

    /**
     * Initialize the component state.
     */
    public function mount(): void
    {
        $this->form = new NotificationStreamForm($this, 'form');
        $this->form->initialize();
    }

    /**
     * Handle changes to the notification stream type.
     *
     * Resets validation for the value field when the type changes.
     */
    public function updatedFormType(): void
    {
        $this->resetValidation('form.value');
    }

    /**
     * Submit the form and create a new notification stream.
     */
    public function submit(): RedirectResponse|Redirector
    {
        $this->form->validate();

        /** @var User $user */
        $user = Auth::user();

        NotificationStream::create([
            'label' => $this->form->label,
            'type' => $this->form->type,
            'value' => $this->form->value,
            'receive_successful_backup_notifications' => $this->form->success_notification ? now() : null,
            'receive_failed_backup_notifications' => $this->form->failed_notification ? now() : null,
            'user_id' => $user->getAttribute('id'),
        ]);

        Toaster::success('Notification stream has been added.');

        return Redirect::route('notification-streams.index');
    }

    /**
     * Render the create notification stream form.
     */
    public function render(): View
    {
        return view('livewire.notification-streams.forms.create-notification-stream')
            ->layout('components.layouts.account-app');
    }
}
