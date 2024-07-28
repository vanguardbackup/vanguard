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

class CreateNotificationStream extends Component
{
    public NotificationStreamForm $form;

    public function mount(): void
    {
        $this->form = new NotificationStreamForm($this, 'form');
        $this->form->initialize();
    }

    public function updatedFormType(): void
    {
        $this->resetValidation('form.value');
    }

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

    public function render(): View
    {
        return view('livewire.notification-streams.forms.create-notification-stream')
            ->layout('components.layouts.account-app');
    }
}
