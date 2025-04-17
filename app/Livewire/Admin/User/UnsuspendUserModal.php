<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Toaster;

class UnsuspendUserModal extends Component
{
    /** @var int The ID of the user */
    public int $userId;

    /** @var string|null An optional reason for lifting the suspension. */
    public ?string $unsuspensionNote = null;

    /** @var bool Determine if we notify the user when the ban is lifted */
    public bool $notifyUserAboutUnsuspension = false;

    public function mount(User|int $user): void
    {
        $this->userId = $user instanceof User
            ? $user->getAttribute('id')
            : $user;
    }

    /**
     * Render the unsuspend modal component.
     */
    public function render(): View
    {
        return view('livewire.admin.unsuspend-modal', [
            'user' => User::findOrFail($this->userId),
        ]);
    }

    /**
     * Handle the unsuspension process.
     */
    public function unsuspendUser(): void
    {
        $user = User::findOrFail($this->userId);

        if (! $user->hasSuspendedAccount()) {
            Log::info('[UNSUSPENSION] Unable to unsuspend user - not currently suspended.');
            Toaster::error('Unable to unsuspend user - not currently suspended.');
            $this->dispatch('close-modal', 'unsuspend-user-modal-' . $user->id);

            return;
        }

        $this->validate([
            'unsuspensionNote' => ['nullable', 'string', 'max:500'],
            'notifyUserAboutUnsuspension' => ['boolean'],
        ]);

        // Get the active suspension record
        $activeSuspension = $user->suspensions()->whereNull('lifted_at')->latest()->first();

        if ($activeSuspension) {
            // Update the suspension record
            $activeSuspension->update([
                'lifted_at' => Carbon::now(),
                'lifted_by_admin_user_id' => Auth::user()->id,
                'unsuspension_note' => $this->unsuspensionNote,
            ]);

            // Handle notification if selected
            if ($this->notifyUserAboutUnsuspension) {
                // You would implement notification logic here
                // For example:
                // $user->notify(new UnsuspensionNotification($activeSuspension));
            }

            Log::info('[UNSUSPENSION] User unsuspended successfully.', ['user_id' => $user->id]);
            Toaster::success('User has been unsuspended successfully.');
        } else {
            Log::info('[UNSUSPENSION] No active suspension found for user.', ['user_id' => $user->id]);
            Toaster::error('No active suspension found for this user.');
        }

        $this->dispatch('close-modal', 'unsuspend-user-modal-' . $user->id);
    }
}
