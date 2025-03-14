<?php

use App\Livewire\Actions\Logout;
use App\Mail\User\DeletionConfirmationMail;
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
new class extends Component {
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
        $this->isEligible =
            $user->backupTasks()->count() === 0 &&
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
            'backupTasks' => [
                'count' => $user->backupTasks()->count(),
                'label' => 'Backup Tasks',
                'icon' => 'hugeicons-archive-02',
                'description' => 'Scheduled backup operations you have configured.',
            ],
            'remoteServers' => [
                'count' => $user->remoteServers()->count(),
                'label' => 'Remote Servers',
                'icon' => 'hugeicons-hard-drive',
                'description' => 'Linux servers you have connected to your account.',
            ],
            'backupDestinations' => [
                'count' => $user->backupDestinations()->count(),
                'label' => 'Backup Destinations',
                'icon' => 'hugeicons-folder-cloud',
                'description' => 'Storage locations (e.g., S3 buckets) for your backups.',
            ],
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

        $user = Auth::user();

        Mail::to($user)
            ->queue(new DeletionConfirmationMail($user));

        tap($user, $logout(...))->delete();

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

                <div class="mb-8 p-6">
                    <div class="space-y-3">
                        <p>
                            {{ __('We want to ensure you fully understand the implications of deleting your account. This action is permanent and irreversible, affecting all aspects of your Vanguard experience.') }}
                        </p>
                        <p>
                            {{ __('Once you proceed, all your personal information will be permanently erased from our systems. This includes any backup tasks you\'ve set up, remote servers you\'ve connected, and backup destinations you\'ve configured.') }}
                        </p>
                        <p>
                            {{ __('You\'ll no longer have access to any services or features associated with your Vanguard account. It\'s important to note that we won\'t be able to recover this information once it\'s gone.') }}
                        </p>
                    </div>
                </div>

                @if (! $hasPassword)
                    <x-notice
                        type="info"
                        title="{{ __('Account Deletion Requires Password') }}"
                        text="{{ __('You need to set a password before you can delete your account. Please request a password reset.') }}"
                    />
                @endif

                @if ($hasPassword)
                    <div class="flex items-center justify-end">
                        <x-danger-button wire:click="proceedToEligibilityCheck" type="button">
                            {{ __('Proceed to Eligibility Check') }}
                            @svg('hugeicons-arrow-right-03', 'ml-2 inline h-5 w-5')
                        </x-danger-button>
                    </div>
                @endif
            </x-form-wrapper>
        @elseif ($currentView === 'eligibility')
            <x-form-wrapper>
                <x-slot name="title">
                    {{ __('Account Deletion Eligibility') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('We\'ll check if your account is eligible for deletion based on your current data and services.') }}
                </x-slot>

                <x-slot name="icon">hugeicons-check-list</x-slot>

                <div class="mb-8">
                    @if ($isEligible)
                        <x-notice
                            type="success"
                            title="{{ __('Eligible Account') }}"
                            text="{{ __('Your account is eligible for deletion.') }}"
                        />
                    @else
                        <x-notice
                            type="error"
                            title="{{ __('Ineligible Account') }}"
                            text="{{ __('Your account is not eligible for deletion. Please remove your backup tasks, remote servers and backup destinations before proceeding.') }}"
                        />
                    @endif
                </div>

                <div class="mb-8">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('Account Summary') }}
                    </h3>
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Review the following summary of your account. To be eligible for deletion, all counts must be zero.') }}
                    </p>
                    <ul class="space-y-4">
                        @foreach (['backupTasks', 'remoteServers', 'backupDestinations'] as $key)
                            <li class="rounded-lg bg-gray-100 p-4 dark:bg-gray-800">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="flex items-center text-gray-700 dark:text-gray-300">
                                        <x-dynamic-component
                                            :component="$accountSummary[$key]['icon']"
                                            class="mr-2 inline h-5 w-5"
                                        />
                                        {{ __($accountSummary[$key]['label']) }}
                                    </span>
                                    <span
                                        class="{{ $accountSummary[$key]['count'] > 0 ? 'text-red-500 dark:text-red-400' : 'text-green-500 dark:text-green-400' }} font-semibold"
                                    >
                                        {{ $accountSummary[$key]['count'] }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __($accountSummary[$key]['description']) }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex items-center justify-between">
                    <x-secondary-button wire:click="$set('currentView', 'notice')" type="button">
                        @svg('hugeicons-arrow-left-03', 'mr-2 h-5 w-5')
                        {{ __('Go Back') }}
                    </x-secondary-button>
                    @if ($isEligible)
                        <x-danger-button wire:click="proceedToFinalConfirmation" type="button">
                            {{ __('Proceed to Final Confirmation') }}
                            @svg('hugeicons-arrow-right-03', 'ml-2 inline h-5 w-5')
                        </x-danger-button>
                    @endif
                </div>
            </x-form-wrapper>
        @elseif ($currentView === 'final-confirmation')
            <x-form-wrapper>
                <x-slot name="title">
                    {{ __('Final Account Deletion Confirmation') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('This is your last chance to reconsider. Once confirmed, your account will be deleted.') }}
                </x-slot>
                <x-slot name="icon">hugeicons-user-shield-01</x-slot>

                <form wire:submit.prevent="deleteUser">
                    <div class="mb-8">
                        <x-notice
                            type="warning"
                            title="{{ __('Final Warning') }}"
                            text="{{ __('You are about to permanently delete your account. This action cannot be undone.') }}"
                        />

                        <div class="mt-4">
                            <x-input-label for="password" :value="__('Confirm Your Password')"/>
                            <x-text-input
                                name="password"
                                wire:model="password"
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                required
                                autocomplete="current-password"
                            />
                            <x-input-error :messages="$errors->get('password')" class="mt-2"/>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <x-secondary-button wire:click="$set('currentView', 'eligibility')" type="button">
                            @svg('hugeicons-arrow-left-03', 'mr-2 h-5 w-5')
                            {{ __('Go Back') }}
                        </x-secondary-button>
                        <x-danger-button type="submit">
                            {{ __('Permanently Delete Account') }}
                            @svg('hugeicons-sad-01', '-mt-0.5 ml-1 inline h-5 w-5')
                        </x-danger-button>
                    </div>
                </form>
            </x-form-wrapper>
        @endif
    </div>
</div>
