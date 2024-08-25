<?php

use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

/**
 * Delete User Form Component
 *
 * This component handles the account deletion process, including eligibility checks
 * and final confirmation.
 */
new class extends Component
{
    /** @var string The current view of the deletion process */
    public string $currentView = 'notice';

    /** @var string The user's password for confirmation */
    public string $password = '';

    /** @var bool Whether the user is eligible for account deletion */
    public bool $isEligible = false;

    /** @var array Summary of the user's account data */
    public array $accountSummary = [];

    /** @var bool Whether the user has set a password */
    public bool $hasPassword = true;

    /**
     * Initialize the component state
     */
    public function mount(): void
    {
        $this->checkEligibility();
        $this->generateAccountSummary();
        $this->checkPassword();
    }

    /**
     * Check if the user is eligible for account deletion
     */
    private function checkEligibility(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->isEligible = $user->backupTasks()->count() === 0 &&
            $user->remoteServers()->count() === 0 &&
            $user->backupDestinations()->count() === 0;
    }

    /**
     * Generate a summary of the user's account data
     */
    private function generateAccountSummary(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->accountSummary = [
            'backupTasks' => $user->backupTasks()->count(),
            'remoteServers' => $user->remoteServers()->count(),
            'backupDestinations' => $user->backupDestinations()->count(),
        ];
    }

    /**
     * Check if the user has set a password
     */
    private function checkPassword(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->hasPassword = !is_null($user->password);
    }

    /**
     * Proceed to the eligibility check view
     */
    public function proceedToEligibilityCheck(): void
    {
        $this->currentView = 'eligibility';
    }

    /**
     * Proceed to the final confirmation view if eligible
     */
    public function proceedToFinalConfirmation(): void
    {
        if ($this->isEligible) {
            $this->currentView = 'final-confirmation';
        }
    }

    /**
     * Delete the user's account
     *
     * @param Logout $logout
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div>
        @if ($currentView === 'notice')
            <x-form-wrapper>
                <x-slot name="title">{{ __('Account Deletion') }}</x-slot>
                <x-slot name="description">
                    {{ __('Please review the consequences of account deletion before proceeding.') }}
                </x-slot>
                <x-slot name="icon">hugeicons-user-remove-01</x-slot>

                <div class="mb-8 p-6 bg-red-50 dark:bg-red-950 rounded-lg">
                    <div class="flex items-center mb-4">
                        @svg('hugeicons-alert-circle', 'w-8 h-8 text-red-500 dark:text-red-400 mr-3')
                        <h3 class="text-xl font-semibold text-red-700 dark:text-red-300">{{ __('Warning: Irreversible Action') }}</h3>
                    </div>
                    <p class="text-red-700 dark:text-red-300 mb-4">
                        {{ __('Deleting your account is a permanent action. Please consider the following consequences:') }}
                    </p>
                    <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-2">
                        <li>{{ __('All your personal information will be permanently removed') }}</li>
                        <li>{{ __('Your backup tasks, remote servers, and backup destinations will be deleted') }}</li>
                        <li>{{ __('You will lose access to all services associated with this account') }}</li>
                        <li>{{ __('This action cannot be undone') }}</li>
                    </ul>
                </div>

                @if (!$hasPassword)
                    <div class="mb-8 p-4 bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 rounded-lg">
                        @svg('hugeicons-alert-02', 'w-6 h-6 inline-block mr-2')
                        {{ __('You need to set a password before you can delete your account. Please request a password reset.') }}
                    </div>
                @endif

                @if ($hasPassword)
                    <div class="flex justify-end items-center">
                        <x-danger-button wire:click="proceedToEligibilityCheck" type="button">
                            @svg('hugeicons-arrow-right-03', 'w-5 h-5 mr-2 inline')
                            {{ __('Proceed to Eligibility Check') }}
                        </x-danger-button>
                    </div>
                @endif
            </x-form-wrapper>

        @elseif ($currentView === 'eligibility')
            <x-form-wrapper>
                <x-slot name="title">{{ __('Account Deletion Eligibility') }}</x-slot>
                <x-slot name="description">
                    {{ __('We\'ll check if your account is eligible for deletion based on your current data and services.') }}
                </x-slot>
                <x-slot name="icon">hugeicons-check-list</x-slot>

                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Account Summary') }}</h3>
                    <ul class="space-y-4">
                        @foreach (['backupTasks' => 'Backup Tasks', 'remoteServers' => 'Remote Servers', 'backupDestinations' => 'Backup Destinations'] as $key => $label)
                            <li class="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                <span class="text-gray-700 dark:text-gray-300">{{ __($label) }}</span>
                                <span class="font-semibold {{ $accountSummary[$key] > 0 ? 'text-red-500 dark:text-red-400' : 'text-green-500 dark:text-green-400' }}">
                                    {{ $accountSummary[$key] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mb-8">
                    @if ($isEligible)
                        <div class="p-4 bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 rounded-lg">
                            @svg('hugeicons-checkmark-circle-02', 'w-6 h-6 inline-block mr-2')
                            {{ __('Your account is eligible for deletion.') }}
                        </div>
                    @else
                        <div class="p-4 bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 rounded-lg">
                            @svg('hugeicons-cancel-circle', 'w-6 h-6 inline-block mr-2')
                            {{ __('Your account is not eligible for deletion. Please remove all associated data and services before proceeding.') }}
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center">
                    <x-secondary-button wire:click="$set('currentView', 'notice')" type="button">
                        @svg('hugeicons-arrow-left-03', 'w-5 h-5 mr-2')
                        {{ __('Go Back') }}
                    </x-secondary-button>
                    @if ($isEligible)
                        <x-danger-button wire:click="proceedToFinalConfirmation" type="button">
                            @svg('hugeicons-arrow-right-03', 'w-5 h-5 mr-2 inline')
                            {{ __('Proceed to Final Confirmation') }}
                        </x-danger-button>
                    @endif
                </div>
            </x-form-wrapper>

        @elseif ($currentView === 'final-confirmation')
            <x-form-wrapper>
                <x-slot name="title">{{ __('Final Account Deletion Confirmation') }}</x-slot>
                <x-slot name="description">
                    {{ __('This is your last chance to reconsider. Once confirmed, your account will be deleted.') }}
                </x-slot>
                <x-slot name="icon">hugeicons-user-shield-01</x-slot>

                <form wire:submit.prevent="deleteUser">
                    <div class="mb-8">
                        <div class="p-6 bg-red-50 dark:bg-red-950 rounded-lg mb-6">
                            <div class="flex items-center mb-4">
                                @svg('hugeicons-alert-02', 'w-8 h-8 text-red-500 dark:text-red-400 mr-3')
                                <h3 class="text-xl font-semibold text-red-700 dark:text-red-300">{{ __('Final Warning') }}</h3>
                            </div>
                            <p class="text-red-700 dark:text-red-300 mb-4">
                                {{ __('You are about to permanently delete your account. This action cannot be undone.') }}
                            </p>
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Confirm Your Password')" />
                            <x-text-input
                                name="password"
                                wire:model="password"
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                required
                                autocomplete="current-password"
                            />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <x-secondary-button wire:click="$set('currentView', 'eligibility')" type="button">
                            @svg('hugeicons-arrow-left-03', 'w-5 h-5 mr-2')
                            {{ __('Go Back') }}
                        </x-secondary-button>
                        <x-danger-button type="submit">
                            @svg('hugeicons-cancel-01', 'w-5 h-5 mr-2 inline')
                            {{ __('Permanently Delete Account') }}
                        </x-danger-button>
                    </div>
                </form>
            </x-form-wrapper>
        @endif
    </div>
</div>
