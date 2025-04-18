<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

use App\Models\User;
use App\Models\UserSuspension;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
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

    public function mount(User|int $user): void
    {
        $this->userId = $user instanceof User
            ? $user->getAttribute('id')
            : $user;
    }

    /**
     * Get the user instance
     */
    public function getUser(): User
    {
        return User::findOrFail($this->userId);
    }

    /**
     * Get the active suspension for the user
     */
    public function getActiveSuspension(): ?UserSuspension
    {
        return $this->getUser()->suspensions()->whereNull('lifted_at')->latest()->first();
    }

    /**
     * Get past suspensions for the user
     *
     * @return Collection<int, UserSuspension>
     */
    public function getPastSuspensions(): Collection
    {
        return $this->getUser()->suspensions()->whereNotNull('lifted_at')->latest()->get();
    }

    /**
     * Render the unsuspend modal component.
     */
    public function render(): View
    {
        $user = $this->getUser();
        $activeSuspension = $this->getActiveSuspension();
        $pastSuspensions = $this->getPastSuspensions();

        return view('livewire.admin.unsuspend-modal', [
            'user' => $user,
            'activeSuspension' => $activeSuspension,
            'pastSuspensions' => $pastSuspensions,
        ]);
    }

    /**
     * Handle the unsuspension process.
     */
    public function unsuspendUser(): void
    {
        $user = $this->getUser();

        if (! $user->hasSuspendedAccount()) {
            Log::info('[UNSUSPENSION] Unable to unsuspend user - not currently suspended.');
            Toaster::error('Unable to unsuspend user - not currently suspended.');
            $this->dispatch('close-modal', 'unsuspend-user-modal-' . $user->getAttribute('id'));

            return;
        }

        $this->validate([
            'unsuspensionNote' => ['nullable', 'string'],
        ]);

        // Get the active suspension record
        $activeSuspension = $this->getActiveSuspension();

        /** @var User $user */
        $authUser = Auth::user();

        if ($activeSuspension instanceof UserSuspension) {
            // Update the suspension record
            $activeSuspension->update([
                'lifted_at' => Carbon::now(),
                'lifted_by_admin_user_id' => $authUser?->id,
                'unsuspension_note' => $this->unsuspensionNote,
            ]);

            Log::info('[UNSUSPENSION] User unsuspended successfully.', ['user_id' => $user->getAttribute('id')]);
            Toaster::success('User has been unsuspended successfully.');
        } else {
            Log::info('[UNSUSPENSION] No active suspension found for user.', ['user_id' => $user->getAttribute('id')]);
            Toaster::error('No active suspension found for this user.');
        }

        $this->dispatch('close-modal', 'unsuspend-user-modal-' . $user->getAttribute('id'));
        $this->dispatch('refreshUserTable');
    }
}
