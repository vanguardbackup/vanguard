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
            'user_id' => $user->getAttribute('id'),
        ]);

        Toaster::success(__('Notification stream has been added.'));

        return Redirect::route('notification-streams.index');
    }

    public function render(): View
    {
        return view('livewire.notification-streams.forms.create-notification-stream');
    }
}
