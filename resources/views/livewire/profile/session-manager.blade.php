<?php

use Cjmellor\BrowserSessions\Facades\BrowserSessions;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;

/**
 * Session Manager Component
 *
 * This component manages and displays active user sessions across various devices.
 * It provides functionality to view, terminate individual sessions, and log out from all other sessions.
 */
new class extends Component {
    use WithRateLimiting;

    /**
     * Collection of active user sessions.
     *
     * @var Collection
     */
    public Collection $sessions;

    /**
     * User's password for authentication when logging out other sessions.
     *
     * @var string
     */
    #[Rule('required|string')]
    public string $password = '';

    /**
     * Initialize the component and load active sessions.
     *
     * @return void
     */
    public function mount(): void
    {
        $this->loadSessions();
    }

    /**
     * Load active sessions for the authenticated user.
     *
     * @return void
     */
    public function loadSessions(): void
    {
        if (!Auth::check()) {
            return;
        }

        $this->sessions = BrowserSessions::sessions();
    }

    /**
     * Log out from all other browser sessions.
     *
     * @return void
     */
    public function logoutOtherBrowserSessions(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        BrowserSessions::logoutOtherBrowserSessions();
        $this->loadSessions();
        $this->password = '';
        $this->dispatch('close-modal', 'confirm-logout-other-browser-sessions');
        Toaster::success('All other browser sessions have been successfully terminated.');
    }

    /**
     * Terminate a specific session.
     *
     * @param string $sessionId
     * @return void
     */
    public function logoutSession(string $sessionId): void
    {
        if (!Auth::check()) {
            return;
        }

        DB::table(config('session.table'))
            ->where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->delete();

        $this->loadSessions();
        Toaster::success('The selected session has been successfully terminated.');
    }

    /**
     * Check if the current session driver is set to database.
     *
     * @return bool
     */
    #[Computed]
    public function isDatabaseDriver(): bool
    {
        return Config::get('session.driver') === 'database';
    }

    /**
     * Get the user's last activity in a human-readable format.
     *
     * @return string
     */
    #[Computed]
    public function userLastActivity(): string
    {
        return BrowserSessions::getUserLastActivity(human: true);
    }
}; ?>

<div wire:key="{{ auth()->id() }}-browser-sessions">
    <div wire:key="current-view-sessions">
        @if (!$this->isDatabaseDriver)
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
                <p class="font-bold">{{ __('Warning') }}</p>
                <p>{{ __('The session driver is not configured to use the database. Session management functionality requires the database driver to operate correctly. Please update your session configuration to use the database driver.') }}</p>
            </div>
        @else
            <x-form-wrapper>
                <x-slot name="title">{{ __('Active Sessions') }}</x-slot>
                <x-slot name="description">
                    {{ __('Monitor and manage your active login sessions across various devices and locations.') }}
                </x-slot>
                <x-slot name="icon">heroicon-o-globe-alt</x-slot>

                <div class="space-y-6">
                    @foreach ($this->sessions as $session)
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg transition-all duration-200 overflow-hidden">
                            <div class="p-6">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center mb-4 sm:mb-0">
                                        <div class="flex-shrink-0 mr-4">
                                            @if ($session->device['desktop'])
                                                @svg('heroicon-o-computer-desktop', 'w-10 h-10 text-gray-500 dark:text-gray-400')
                                            @elseif ($session->device['mobile'])
                                                @svg('heroicon-o-device-phone-mobile', 'w-10 h-10 text-gray-500 dark:text-gray-400')
                                            @elseif ($session->device['tablet'])
                                                @svg('heroicon-o-device-tablet', 'w-10 h-10 text-gray-500 dark:text-gray-400')
                                            @else
                                                @svg('heroicon-o-globe-alt', 'w-10 h-10 text-gray-500 dark:text-gray-400')
                                            @endif
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $session->device['browser'] }} on {{ $session->device['platform'] }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                {{ $session->ip_address }} -
                                                @if ($session->is_current_device)
                                                    <span class="text-green-500 font-semibold">{{ __('Current device') }}</span>
                                                @else
                                                    {{ __('Last active') }} {{ $session->last_active }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex justify-end sm:ml-4 sm:flex-shrink-0">
                                        @if ($session->is_current_device)
                                            <span class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-green-700 bg-green-100 dark:bg-green-800 dark:text-green-100">
                                                {{ __('Current Device') }}
                                            </span>
                                        @else
                                            <x-danger-button
                                                wire:click="logoutSession('{{ $session->id }}')"
                                                wire:loading.attr="disabled"
                                                class="w-full sm:w-auto justify-center"
                                            >
                                                {{ __('Terminate') }}
                                            </x-danger-button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if (!$session->is_current_device)
                                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('Device type') }}: {{ $session->device['desktop'] ? 'Desktop' : ($session->device['mobile'] ? 'Mobile' : ($session->device['tablet'] ? 'Tablet' : 'Unknown')) }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 p-6 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-sm">
                    <div class="flex items-center mb-4">
                        @svg('heroicon-o-arrow-left-on-rectangle', 'w-8 h-8 text-gray-500 dark:text-gray-400 mr-3')
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('Terminate Other Sessions') }}</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('For enhanced security, you can terminate all other active sessions across your devices. If you suspect any unauthorized access, it\'s recommended to change your password immediately after this action.') }}</p>
                    <x-danger-button
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'confirm-logout-other-browser-sessions')"
                    >
                        {{ __('Terminate Other Sessions') }}
                    </x-danger-button>
                </div>

                <x-modal name="confirm-logout-other-browser-sessions" :show="$errors->isNotEmpty()" focusable>
                    <x-slot name="title">
                        {{ __('Terminate Other Sessions') }}
                    </x-slot>
                    <x-slot name="description">
                        {{ __('Enhance your account security by terminating all sessions on other devices.') }}
                    </x-slot>
                    <x-slot name="icon">
                        heroicon-o-arrow-left-on-rectangle
                    </x-slot>

                    <form wire:submit="logoutOtherBrowserSessions">
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Please enter your password to confirm that you want to terminate all other active sessions across your devices. This action cannot be reversed.') }}
                        </p>

                        <div class="mt-6">
                            <x-input-label for="password" value="{{ __('Password') }}" class="sr-only"/>

                            <x-text-input
                                wire:model="password"
                                id="password"
                                name="password"
                                type="password"
                                class="mt-1 block w-full"
                                placeholder="{{ __('Password') }}"
                            />

                            <x-input-error :messages="$errors->get('password')" class="mt-2"/>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-secondary-button x-on:click="$dispatch('close')">
                                {{ __('Cancel') }}
                            </x-secondary-button>

                            <x-danger-button class="ml-3">
                                {{ __('Terminate Other Sessions') }}
                            </x-danger-button>
                        </div>
                    </form>
                </x-modal>
            </x-form-wrapper>
        @endif
    </div>
</div>
