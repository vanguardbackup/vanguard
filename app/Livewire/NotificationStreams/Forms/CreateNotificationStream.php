<?php

declare(strict_types=1);

namespace App\Livewire\NotificationStreams\Forms;

use App\Livewire\NotificationStreams\Forms\Traits\LogsJsErrors;
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
    use LogsJsErrors;

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
     * Resets validation for the value field and additional fields when the type changes.
     */
    public function updatedFormType(): void
    {
        $this->resetValidation(['form.value', 'form.additional_field_one', 'form.additional_field_two']);
    }

    /**
     * Submit the form and create a new notification stream.
     */
    public function submit(): RedirectResponse|Redirector
    {
        $this->form->validate();

        /** @var User $user */
        $user = Auth::user();

        $data = [
            'label' => $this->form->label,
            'type' => $this->form->type,
            'value' => $this->form->value,
            'receive_successful_backup_notifications' => $this->form->success_notification ? now() : null,
            'receive_failed_backup_notifications' => $this->form->failed_notification ? now() : null,
            'user_id' => $user->getAttribute('id'),
        ];

        // Add additional fields if they are set and not null
        if (! is_null($this->form->additional_field_one)) {
            $data['additional_field_one'] = $this->form->additional_field_one;
        }
        if (! is_null($this->form->additional_field_two)) {
            $data['additional_field_two'] = $this->form->additional_field_two;
        }

        NotificationStream::create($data);

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
