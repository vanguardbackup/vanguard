<?php

use App\Mail\User\WelcomeMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        $user->forceFill([
            'registration_ip' => request()->ip()
        ]);

        $user->save();

        Mail::to($user->email)->queue(new WelcomeMail($user));

        Auth::login($user);

        $this->redirect(route('overview', absolute: false), navigate: true);
    }
}; ?>

<div>
    @if (config('registration.user_registration_enabled'))
        <x-slot name="title">
            {{ __('Create an account') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Give us some information about yourself!') }}
        </x-slot>
        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />
        <x-auth-session-error class="mb-4" :loginError="session('loginError')" />

        <form wire:submit="register" class="mt-8 space-y-6">
            <!-- Name -->
            <div>
                <x-input-label
                    for="name"
                    :value="__('Name')"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                />
                <x-text-input
                    dusk="name"
                    wire:model="name"
                    id="name"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    type="text"
                    name="name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Email Address -->
            <div>
                <x-input-label
                    for="email"
                    :value="__('Email Address')"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                />
                <x-text-input
                    dusk="email"
                    wire:model="email"
                    id="email"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    type="email"
                    name="email"
                    required
                    autocomplete="username"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
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
                    wire:model="password"
                    id="password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div>
                <x-input-label
                    for="password_confirmation"
                    :value="__('Confirm Password')"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                />
                <x-text-input
                    dusk="password_confirmation"
                    wire:model="password_confirmation"
                    id="password_confirmation"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <x-primary-button
                dusk="create_account_button"
                class="w-full justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600"
            >
                {{ __('Create Account') }}
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
                            <span>{{ __('Sign up with GitHub') }}</span>
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
                    {{ __('Already have an account?') }}
                    <a
                        href="{{ route('login') }}"
                        class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                        wire:navigate
                    >
                        {{ __('Sign In') }}
                    </a>
                </div>
            </div>
        </form>
    @else
        <div class="flex items-center justify-center">
            <div class="text-center">
                {{
                    __('Unfortunately, the admin has disabled the registration for new users on this web
                                                                                                                                                                                                                                                                                                                                                                                                                            service.')
                }}
            </div>
        </div>
    @endif
</div>
