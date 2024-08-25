<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ], [
                'current_password.required' => __('Please enter your password.'),
                'current_password.current_password' => __('The password you have entered is incorrect. Please try again.'),
                'password.required' => __('Please enter your password.'),
                'password.confirmed' => __('Please confirm your password.'),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<x-form-wrapper>
    <x-slot name="title">
        {{ __('Update Password') }}
    </x-slot>
    <x-slot name="description">
        {{ __('Ensure your account is using a long, random password to stay secure.') }}
    </x-slot>
    <x-slot name="icon">
        hugeicons-password-validation
    </x-slot>
    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2 grid md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="update_password_current_password" :value="__('Current Password')" />
                    <x-text-input
                        wire:model="current_password"
                        id="update_password_current_password"
                        name="current_password"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                    />
                    <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="update_password_password" :value="__('New Password')" />
                    <x-text-input
                        wire:model="password"
                        id="update_password_password"
                        name="password"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                    />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                <x-text-input
                    wire:model="password_confirmation"
                    id="update_password_password_confirmation"
                    name="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>
        <div class="mt-6 pb-4 max-w-3xl mx-auto">
            <div class="flex justify-center">
                <div class="w-full sm:w-4/6">
                    <x-primary-button type="submit" class="w-full justify-center" centered action="submit">
                        {{ __('Save') }}
                    </x-primary-button>
                </div>
            </div>
        </div>
    </form>
</x-form-wrapper>
