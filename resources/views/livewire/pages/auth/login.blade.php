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
    <x-auth-session-status class="mb-4" :status="session('status')"/>
    <x-auth-session-error class="mb-4" :loginError="session('loginError')"/>

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')"/>
            <x-text-input wire:model="form.email" id="email" class="block mt-2.5 w-full" type="email" name="email"
                          autofocus autocomplete="email"/>
            <x-input-error :messages="$errors->get('form.email')" class="mt-2"/>
        </div>

        <!-- Password -->
        <div class="mt-8">
            <x-input-label for="password" :value="__('Password')"/>

            <x-text-input wire:model="form.password" id="password" class="block mt-2.5 w-full"
                          type="password"
                          name="password"
                          autocomplete="current-password"/>

            <x-input-error :messages="$errors->get('form.password')" class="mt-2"/>
        </div>

        <!-- Remember Me -->
        <div class="block mt-8 mb-4">
            <div class="flex justify-between">
                <div>
                    <label for="remember" class="inline-flex items-center">
                        <x-checkbox name="remember" wire:model="form.remember" id="remember" class="-mt-2"/>
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('Remember me') }}</span>
                    </label>
                </div>
                <div>
                    <div class="inline-flex items-center mt-1">
                        @if (Route::has('password.request'))
                            <a class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                               href="{{ route('password.request') }}" wire:navigate>
                                {{ __('Can\'t remember your password?') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <x-primary-button class="mt-4 my-3" centered fat>
            {{ __('Login') }}
            @svg('heroicon-o-arrow-right', 'w-5 h-5 ms-2 inline')
        </x-primary-button>

        @if (config('services.github.client_id') && config('services.github.client_secret'))
            <div class="flex justify-evenly mt-2">
                <a href="{{ route('github.redirect') }}">
                    <x-secondary-button>
                        <x-icons.github class="w-5 h-5 mr-3"/>
                        {{ __('Login with GitHub') }}
                    </x-secondary-button>
                </a>
            </div>
        @endif

        <div class="text-center mt-5 my-3">
            <div class="text-sm text-gray-600 dark:text-gray-400 my-3.5">
                {{ __('By creating an account, you agree to our Terms of Service and our Privacy Policy.') }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Don\'t have an account?') }}
                <a href="{{ route('register') }}"
                   class="font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300"
                   wire:navigate>
                    {{ __('Sign Up') }}
                </a>
            </div>
        </div>
    </form>
</div>
