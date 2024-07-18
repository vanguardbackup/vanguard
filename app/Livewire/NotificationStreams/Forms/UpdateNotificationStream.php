<?php

declare(strict_types=1);

namespace App\Livewire\NotificationStreams\Forms;

use App\Models\NotificationStream;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

class UpdateNotificationStream extends Component
{
    use AuthorizesRequests;

    public NotificationStream $notificationStream;
    public NotificationStreamForm $form;

    public function mount(NotificationStream $notificationStream): void
    {
        $this->authorize('update', $notificationStream);

        $this->notificationStream = $notificationStream;
        $this->form = new NotificationStreamForm($this, 'form');
        $this->form->initialize();
        $this->form->setNotificationStream($notificationStream);
    }

    public function updatedFormType(): void
    {
        $this->resetValidation('form.value');
    }

    public function submit(): RedirectResponse|Redirector
    {
        $this->authorize('update', $this->notificationStream);

        $this->form->validate();

        $this->notificationStream->update([
            'label' => $this->form->label,
            'type' => $this->form->type,
            'value' => $this->form->value,
            'receive_successful_backup_notifications' => $this->form->success_notification ? now() : null,
            'receive_failed_backup_notifications' => $this->form->failed_notification ? now() : null,
        ]);

        Toaster::success(__('Notification stream has been updated.'));

        return Redirect::route('notification-streams.index');
    }

    public function render(): View
    {
        return view('livewire.notification-streams.forms.update-notification-stream', [
            'notificationStream' => $this->notificationStream,
        ]);
    }
}
