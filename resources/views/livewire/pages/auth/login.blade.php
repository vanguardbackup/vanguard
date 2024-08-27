<?php

use App\Livewire\Forms\LoginForm;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    public function mount(): void
    {
        if (config('app.env') === 'local' && User::where('email', 'test@example.com')->exists()) {
            $this->form->email = 'test@example.com';
            $this->form->password = 'password';
        }
    }

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('overview', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-slot name="title">
        {{ __('Welcome back!') }}
    </x-slot>
    <x-slot name="description">
        {{ __('Please enter your credentials to access your account.') }}
    </x-slot>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <x-auth-session-error class="mb-4" :loginError="session('loginError')" />

    <form wire:submit="login" class="mt-8 space-y-6">
        <!-- Email Address -->
        <div>
            <x-input-label
                for="email"
                :value="__('Email Address')"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            />
            <x-text-input
                dusk="email"
                wire:model="form.email"
                id="email"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                type="email"
                name="email"
                autofocus
                autocomplete="email"
                required
            />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label
                for="password"
                :value="__('Password')"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            />
            <x-text-input
                dusk="password"
                wire:model="form.password"
                id="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                type="password"
                name="password"
                autocomplete="current-password"
                required
            />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <x-checkbox
                    dusk="remember"
                    wire:model="form.remember"
                    id="remember"
                    name="remember"
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                />
                <label for="remember" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                    {{ __('Remember me') }}
                </label>
            </div>

            <div class="text-sm">
                @if (Route::has('password.request'))
                    <a
                        class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                        href="{{ route('password.request') }}"
                        wire:navigate
                    >
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
        </div>

        <x-primary-button
            dusk="login-button"
            class="w-full justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600"
        >
            {{ __('Login') }}
            @svg('hugeicons-arrow-right-02', 'ms-2 inline h-5 w-5')
        </x-primary-button>

        @if (config('services.github.client_id') && config('services.github.client_secret'))
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="bg-white px-2 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            Or continue with
                        </span>
                    </div>
                </div>

                <div class="mt-6">
                    <a
                        href="{{ route('github.redirect') }}"
                        class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <x-icons.github class="mr-3 h-5 w-5" />
                        <span>{{ __('Login with GitHub') }}</span>
                    </a>
                </div>
            </div>
        @endif

        @if (config('services.gitlab.client_id') && config('services.gitlab.client_secret'))
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                    </div>
                </div>

                <div class="mt-6">
                    <a
                        href="{{ route('gitlab.redirect') }}"
                        class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <x-icons.gitlab class="mr-3 h-5 w-5" />
                        <span>{{ __('Login with GitLab') }}</span>
                    </a>
                </div>
            </div>
        @endif

        @if (config('services.bitbucket.client_id') && config('services.bitbucket.client_secret'))
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                    </div>
                </div>

                <div class="mt-6">
                    <a
                        href="{{ route('bitbucket.redirect') }}"
                        class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <x-icons.bitbucket class="mr-3 h-5 w-5" />
                        <span>{{ __('Login with Bitbucket') }}</span>
                    </a>
                </div>
            </div>
        @endif

        <div class="mt-8 text-center">
            @php
                $showTerms = config('app.terms_of_service_url');
                $showPrivacy = config('app.privacy_policy_url');
            @endphp

            @if ($showTerms || $showPrivacy)
                <div class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('By creating an account, you agree to our') }}
                    @if ($showTerms)
                        <a
                            href="{{ config('app.terms_of_service_url') }}"
                            class="underline hover:text-gray-700 dark:hover:text-gray-300"
                        >
                            {{ __('Terms of Service') }}
                        </a>
                    @endif

                    @if ($showTerms && $showPrivacy)
                        {{ __('and') }}
                    @endif

                    @if ($showPrivacy)
                        <a
                            href="{{ config('app.privacy_policy_url') }}"
                            class="underline hover:text-gray-700 dark:hover:text-gray-300"
                        >
                            {{ __('Privacy Policy') }}
                        </a>
                    @endif

                    {{ __('.') }}
                </div>
            @endif

            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Don\'t have an account?') }}
                <a
                    href="{{ route('register') }}"
                    class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                    wire:navigate
                >
                    {{ __('Sign Up') }}
                </a>
            </div>
        </div>
    </form>
</div>
