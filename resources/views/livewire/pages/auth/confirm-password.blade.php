<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (
            ! Auth::guard('web')->validate([
                'email' => Auth::user()->email,
                'password' => $this->password,
            ])
        ) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('overview', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-slot name="title">
        {{ __('Confirm Password') }}
    </x-slot>
    <x-slot name="description">
        {{ __('Please confirm your password to continue.') }}
    </x-slot>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <x-auth-session-error class="mb-4" :loginError="session('loginError')" />

    <form wire:submit="confirmPassword" class="mt-8 space-y-6">
        <!-- Password -->
        <div>
            <x-input-label
                for="password"
                :value="__('Password')"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            />
            <x-text-input
                wire:model="password"
                id="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button
            class="w-full justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600"
        >
            {{ __('Confirm') }}
            @svg('hugeicons-arrow-right-02', 'ms-2 inline h-5 w-5')
        </x-primary-button>

        <div class="mt-8 text-center">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Forgot your password?') }}
                <a
                    href="{{ route('password.request') }}"
                    class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                    wire:navigate
                >
                    {{ __('Reset it here') }}
                </a>
            </div>
        </div>
    </form>
</div>
