<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class SuspensionHistoryModal extends Component
{
    /** @var int The ID of the user */
    public int $userId;

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
     * Get all suspensions for the user
     */
    public function getAllSuspensions(): Collection
    {
        return $this->getUser()->suspensions()->latest('suspended_at')->get();
    }

    /**
     * Render the suspension history modal component.
     */
    public function render(): View
    {
        $user = $this->getUser();
        $activeSuspension = $this->getActiveSuspension();
        $allSuspensions = $this->getAllSuspensions();

        return view('livewire.admin.suspension-history-modal', [
            'user' => $user,
            'activeSuspension' => $activeSuspension,
            'allSuspensions' => $allSuspensions,
        ]);
    }
}
