<?php

use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Jenssegers\Agent\Agent;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;

/**
 * Session Manager Component
 *
 * Manages and displays active user sessions across various devices.
 * Provides functionality to view, terminate individual sessions, and log out from all other sessions.
 */
new class extends Component {
    use WithRateLimiting;

    /** @var Collection<int, object> Active user sessions. */
    public Collection $sessions;

    /** @var string User's password for authentication when logging out other sessions. */
    #[Rule('required|string')]
    public string $password = '';

    /** @var object|null Currently selected session for detailed view. */
    public ?object $selectedSession = null;

    public function mount(): void
    {
        $this->loadSessions();
    }

    public function loadSessions(): void
    {
        if (!Auth::check()) {
            return;
        }

        $this->sessions = $this->getSessions();
    }

    public function logoutOtherBrowserSessions(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $this->doLogoutOtherBrowserSessions();
        $this->loadSessions();
        $this->password = '';
        $this->dispatch('close-modal', 'confirm-logout-other-browser-sessions');
        Toaster::success(__('All other browser sessions have been successfully terminated.'));
    }

    public function logoutSession(string $sessionId): void
    {
        if (!Auth::check()) {
            return;
        }

        DB::table(Config::get('session.table', 'sessions'))
            ->where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->delete();

        $this->loadSessions();
        $this->selectedSession = null;
        $this->dispatch('close-modal', 'session-details');
        Toaster::success(__('The selected session has been successfully terminated.'));
    }

    public function showSessionDetails(string $sessionId): void
    {
        $this->selectedSession = $this->sessions->firstWhere('id', $sessionId);
        $this->dispatch('open-modal', 'session-details');
    }

    #[Computed]
    public function isDatabaseDriver(): bool
    {
        return Config::get('session.driver') === 'database' && request()->hasSession();
    }

    #[Computed]
    public function userLastActivity(): string
    {
        return $this->getUserLastActivity(true);
    }

    protected function getSessions(): Collection
    {
        if (!$this->isDatabaseDriver) {
            return collect();
        }

        return collect(
            DB::connection(Config::get('session.connection'))
                ->table(Config::get('session.table', 'sessions'))
                ->where('user_id', Auth::id())
                ->latest('last_activity')
                ->get()
        )->map(function ($session) {
            $agent = $this->createAgent($session);
            $location = $this->getLocationFromIp($session->ip_address);

            return (object) [
                'id' => $session->id,
                'device' => [
                    'browser' => $agent->browser(),
                    'desktop' => $agent->isDesktop(),
                    'mobile' => $agent->isMobile(),
                    'tablet' => $agent->isTablet(),
                    'platform' => $agent->platform(),
                ],
                'ip_address' => $session->ip_address,
                'is_current_device' => $session->id === request()->session()->getId(),
                'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'location' => $location,
            ];
        });
    }

    protected function createAgent(object $session): Agent
    {
        return tap(
            new Agent(),
            fn (Agent $agent) => $agent->setUserAgent($session->user_agent)
        );
    }

    protected function getLocationFromIp(string $ip): array
    {
        try {
            $response = Http::get("http://ip-api.com/json/{$ip}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'city' => $data['city'] ?? 'Unknown',
                    'country' => $data['country'] ?? 'Unknown',
                    'latitude' => $data['lat'] ?? 0,
                    'longitude' => $data['lon'] ?? 0,
                ];
            }
        } catch (\Exception $e) {
            // Log the error if needed
        }

        return [
            'city' => 'Unknown',
            'country' => 'Unknown',
            'latitude' => 0,
            'longitude' => 0,
        ];
    }

    protected function doLogoutOtherBrowserSessions(): void
    {
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'user' => [__('User not found.')],
            ]);
        }

        if (!Hash::check($this->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        Auth::guard()->logoutOtherDevices($this->password);

        $this->deleteOtherSessionRecords();
    }

    protected function deleteOtherSessionRecords(): void
    {
        if (!$this->isDatabaseDriver) {
            return;
        }

        DB::connection(Config::get('session.connection'))
            ->table(Config::get('session.table', 'sessions'))
            ->where('user_id', Auth::id())
            ->where('id', '!=', request()->session()->getId())
            ->delete();
    }

    protected function getUserLastActivity(bool $human = false): Carbon|string
    {
        $lastActivity = DB::connection(Config::get('session.connection'))
            ->table(Config::get('session.table', 'sessions'))
            ->where('user_id', Auth::id())
            ->latest('last_activity')
            ->first();

        if (!$lastActivity) {
            return $human ? __('Never') : Carbon::now();
        }

        $timestamp = Carbon::createFromTimestamp($lastActivity->last_activity);
        return $human ? $timestamp->diffForHumans() : $timestamp;
    }
}
?>

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
                                        <x-secondary-button
                                            wire:click="showSessionDetails('{{ $session->id }}')"
                                            class="mr-2"
                                        >
                                            {{ __('See More') }}
                                        </x-secondary-button>
                                        @if (!$session->is_current_device)
                                            <x-danger-button
                                                wire:click="logoutSession('{{ $session->id }}')"
                                                wire:loading.attr="disabled"
                                                class="w-full sm:w-auto justify-center"
                                            >
                                                {{ __('Terminate') }}
                                            </x-danger-button>
                                        @else
                                            <span class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-green-700 bg-green-100 dark:bg-green-800 dark:text-green-100">
                                                {{ __('Current Device') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
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
                                autofocus
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

                <x-modal name="session-details" focusable>
                    <x-slot name="title">
                        {{ __('Session Details') }}
                    </x-slot>
                    <x-slot name="description">
                        {{ __('Detailed information about the selected session.') }}
                    </x-slot>
                    <x-slot name="icon">
                        heroicon-o-information-circle
                    </x-slot>

                    @if ($selectedSession)
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                {{ $selectedSession->device['browser'] }} on {{ $selectedSession->device['platform'] }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <strong>{{ __('IP Address') }}:</strong> {{ $selectedSession->ip_address }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <strong>{{ __('Last Active') }}:</strong> {{ $selectedSession->last_active }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                <strong>{{ __('Location') }}:</strong> {{ $selectedSession->location['city'] }}, {{ $selectedSession->location['country'] }}
                            </p>

                            @if (!$selectedSession->is_current_device)
                                <div class="mt-6">
                                    <x-danger-button
                                        wire:click="logoutSession('{{ $selectedSession->id }}')"
                                        wire:loading.attr="disabled"
                                        class="w-full justify-center"
                                    >
                                        {{ __('Terminate This Session') }}
                                    </x-danger-button>
                                </div>
                            @endif
                        </div>
                    @endif
                </x-modal>
            </x-form-wrapper>
        @endif
    </div>
</div>
