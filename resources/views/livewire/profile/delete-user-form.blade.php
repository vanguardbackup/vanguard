<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ], [
            'password.required' => __('Please enter your password.'),
            'password.current_password' => __('The password you have entered is incorrect. Please try again.')
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Remove Account') }}
        </h2>

        @if (Auth::user()->backupTasks->count() > 0)
            <div class="py-2 px-4 bg-amber-50 text-amber-600 border-l-4 border-amber-600 font-normal my-6">
                @svg('heroicon-o-exclamation-triangle', 'h-5 w-5 inline mr-2')
                <span>
                    {{ __('Your scheduled tasks will not be ran if you remove your account.') }}
                </span>
            </div>
        @endif

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Before removing your account, please take a moment to download any data or information that you wish to retain.') }}
        </p>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once your account is removed, all of its resources and data will be permanently removed. Additionally, all backup tasks, backup destinations, and linked servers will be removed from our systems.') }}
        </p>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
            {{ __('These resources, such as S3 buckets or Linux servers, will still exist at their respective services (server hosts, Amazon S3, etc.). However, Vanguard will no longer be able to link to them and perform scheduled backups of your data.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        {{ __('Proceed') }}
    </x-danger-button>

<a href="{{ route('overview') }}" wire:navigate>
    <x-secondary-button class="ml-2">
        {{ __('Get me out of here!') }}
    </x-secondary-button>
</a>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Are you certain you want to proceed with removing your account?') }}
            </h2>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('When your account is removed, all the data we hold on you will be permanently erased. This includes any backup tasks, backup destinations you have configured with us, and servers you have linked.') }}
            </p>

            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                {{ __('This action cannot be reversed, so please make sure you are certain about this decision.') }}
            </p>

            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                {{ __('You are welcome to re-join at any time.') }}
            </p>

            <p class="mt-3 text-sm text-gray-800 dark:text-gray-600 font-medium">
                {{ __('If you wish to proceed, please enter your account password to confirm.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Your Password:') }}" />

                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-2 block w-full"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                <x-input-explain>
                    {{ __('To continue with the removal of your account, please enter the password associated with your account. If you do not know, you can reset your password.') }}
                </x-input-explain>
            </div>

            <div class="mt-6 flex justify-end">
                <x-danger-button>
                    {{ __('Remove Account') }}
                </x-danger-button>
                <x-secondary-button x-on:click="$dispatch('close')" class="ms-3">
                    {{ __('Cancel') }}
                </x-secondary-button>
            </div>
        </form>
    </x-modal>
</section>
