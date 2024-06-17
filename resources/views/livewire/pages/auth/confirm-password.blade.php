<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
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


    <form wire:submit="confirmPassword">
        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="password"
                          id="password"
                          class="block mt-2.5 w-full"
                          type="password"
                          name="password"
                          required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-8">
            <x-primary-button fat centered>
                {{ __('Confirm') }}
                @svg('heroicon-o-arrow-right', 'w-5 h-5 ms-2 inline')
            </x-primary-button>
        </div>
    </form>
</div>
