<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Toaster;

class SuspendUserModal extends Component
{
    /** @var int The ID of the user */
    public int $userId;

    /** @var string The categorized reason for the suspension */
    public string $suspensionReason = '';

    /** @var array<string|int, string> The possible reasons for a suspension */
    public array $possibleSuspensionReasons = [];

    /** @var bool Determine if we notify the user when the ban is lifted */
    public bool $notifyUserWhenSuspensionHasBeenLifted = false;

    /** @var bool Suspend the user permanently. */
    public bool $permanentlySuspend = false;

    /** @var string How long to suspend the user for */
    public string $suspendUntil = '';

    /** @var string|null An optional reason. */
    public ?string $privateNote = null;

    public function mount(User|int $user): void
    {
        $this->userId = $user instanceof User
            ? $user->getAttribute('id')
            : $user;

        $this->possibleSuspensionReasons = Config::get('suspensions.reasons');

        // Set default suspend until date to tomorrow
        $this->suspendUntil = Carbon::tomorrow()->format('Y-m-d');
    }

    /**
     * Render the webhook modal component.
     */
    public function render(): View
    {
        return view('livewire.admin.suspend-modal', [
            'user' => User::findOrFail($this->userId),
        ]);
    }

    /**
     * Toggle the permanent suspension status
     */
    public function updatedPermanentlySuspend(): void
    {
        if (! $this->permanentlySuspend && ($this->suspendUntil === '' || $this->suspendUntil === '0')) {
            $this->suspendUntil = Carbon::tomorrow()->format('Y-m-d');
        }
    }

    /**
     *  Handle the suspension.
     */
    public function suspendUser(): void
    {
        $user = User::findOrFail($this->userId);

        if ($user->isAdmin() || $user->hasSuspendedAccount()) {
            Log::info('[SUSPENSION] Unable to suspend user.');
            Toaster::error('Unable to suspend user.');
            $this->dispatch('close-modal', 'suspend-user-modal-' . $user->id);

            return;
        }

        $this->validate([
            'suspensionReason' => ['required', 'string', 'in:' . implode(',', $this->possibleSuspensionReasons)],
            'suspendUntil' => [
                $this->permanentlySuspend ? 'nullable' : 'required',
                $this->permanentlySuspend ? '' : 'date',
                $this->permanentlySuspend ? '' : 'after_or_equal:today',
            ],
            'permanentlySuspend' => ['boolean'],
            'privateNote' => ['nullable', 'string'],
            'notifyUserWhenSuspensionHasBeenLifted' => ['boolean'],
        ], [
            'suspensionReason.required' => __('Please specify a reason.'),
            'suspensionReason.in' => __('Please select a valid reason.'),
            'suspendUntil.required' => __('Please specify an end date for the suspension.'),
            'suspendUntil.after_or_equal' => __('The suspension end date must be today or in the future.'),
        ]);

        $suspendUntil = $this->permanentlySuspend ? null : Carbon::parse($this->suspendUntil);

        $notifyDate = null;

        if ($this->notifyUserWhenSuspensionHasBeenLifted && ! $this->permanentlySuspend && $suspendUntil instanceof Carbon) {
            $notifyDate = $suspendUntil;
        }

        /** @var User $user */
        $authUser = Auth::user();

        $user->suspensions()->create([
            'admin_user_id' => $authUser?->id,
            'suspended_at' => Carbon::now(),
            'suspended_until' => $suspendUntil,
            'suspended_reason' => $this->suspensionReason,
            'private_note' => $this->privateNote,
            'notify_user_upon_suspension_being_lifted_at' => $notifyDate,
        ]);

        // Perform the purge of active sessions!
        purge_user_sessions($user);

        Toaster::success('User has been suspended.');
        $this->dispatch('close-modal', 'suspend-user-modal-' . $user->getAttribute('id'));
        $this->dispatch('refreshUserTable');
    }
}
