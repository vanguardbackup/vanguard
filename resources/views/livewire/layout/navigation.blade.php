<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

/**
 * Navigation component for the application.
 *
 * Handles user navigation, logout functionality, responsive menu toggling, and theme switching.
 */
new class extends Component
{
    /**
     * Indicates whether the mobile menu is open.
     *
     * @var bool
     */
    public bool $isMobileMenuOpen = false;

    /**
     * Indicates whether the user dropdown is open.
     *
     * @var bool
     */
    public bool $isUserDropdownOpen = false;

    /**
     * Perform user logout.
     *
     * @param Logout $logout The logout action
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Toggle the mobile menu.
     */
    public function toggleMobileMenu(): void
    {
        $this->isMobileMenuOpen = !$this->isMobileMenuOpen;
    }

    /**
     * Toggle the user dropdown.
     */
    public function toggleUserDropdown(): void
    {
        $this->isUserDropdownOpen = !$this->isUserDropdownOpen;
    }
}; ?>

<nav x-data="{
    open: @entangle('isMobileMenuOpen'),
    userOpen: @entangle('isUserDropdownOpen'),
    desktopDropdownOpen: false
}" class="bg-primary-950 border-b border-gray-900">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('overview') }}" wire:navigate>
                        <x-application-logo class="block h-6 w-auto fill-current text-white"/>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('overview')" :active="request()->routeIs('overview')" wire:navigate>
                        @svg('heroicon-o-' . (Auth::user()->backupTasks->isNotEmpty() ? 'book-open' : 'rocket-launch'), 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2')
                        {{ __(Auth::user()->backupTasks->isNotEmpty() ? 'Overview' : 'Steps to Get Started') }}
                    </x-nav-link>

                    @if (Auth::user()->backupTasks->isNotEmpty())
                        <x-nav-link :href="route('backup-tasks.index')" :active="request()->routeIs('backup-tasks.*')" wire:navigate>
                            @svg('heroicon-o-archive-box', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2')
                            {{ __('Backup Tasks') }}
                        </x-nav-link>
                    @endif

                    @if (Auth::user()->backupDestinations->isNotEmpty())
                        <x-nav-link :href="route('backup-destinations.index')" :active="request()->routeIs('backup-destinations.*')" wire:navigate>
                            @svg('heroicon-o-globe-europe-africa', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2')
                            {{ __('Backup Destinations') }}
                        </x-nav-link>
                    @endif

                    @if (Auth::user()->remoteServers->isNotEmpty())
                        <x-nav-link :href="route('remote-servers.index')" :active="request()->routeIs('remote-servers.*')" wire:navigate>
                            @svg('heroicon-o-server-stack', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2')
                            {{ __('Remote Servers') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-theme-switcher />
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            @click="desktopDropdownOpen = !desktopDropdownOpen"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-50 bg-transparent hover:text-gray-100 focus:outline-none transition ease-in-out duration-150">
                            <img class="h-8 w-8 rounded-full mr-2 border border-gray-950"
                                 src="{{ Auth::user()->gravatar() }}"
                                 alt="{{ Auth::user()->name }}"/>
                            <div x-data="{ name: @js(auth()->user()->first_name) }" x-text="name"
                                 x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4 transition-transform duration-200 ease-in-out"
                                     xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20"
                                     :class="{'rotate-180': desktopDropdownOpen}">
                                    <path fill-rule="evenodd"
                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('tags.index')" wire:navigate>
                            {{ __('Tags') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('notification-streams.index')" wire:navigate>
                            {{ __('Notification Streams') }}
                        </x-dropdown-link>

                        @if (Auth::user()->isAdmin())
                            <x-dropdown-link href="{{ url('/pulse') }}">
                                Laravel Pulse
                            </x-dropdown-link>
                            <x-dropdown-link href="{{ url('/horizon/dashboard') }}">
                                Laravel Horizon
                            </x-dropdown-link>
                        @endif

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button
                    wire:click="toggleMobileMenu"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:bg-gray-700 focus:text-white transition duration-150 ease-in-out"
                    aria-label="Toggle mobile menu"
                >
                    <svg
                        class="h-6 w-6 transition-opacity duration-200 ease-in-out"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24"
                        :class="{'opacity-0': open, 'opacity-100': !open}"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                    <svg
                        class="h-6 w-6 transition-opacity duration-200 ease-in-out absolute"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24"
                        :class="{'opacity-100': open, 'opacity-0': !open}"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        class="sm:hidden"
    >
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('overview')" :active="request()->routeIs('overview')" wire:navigate>
                @svg('heroicon-o-' . (Auth::user()->backupTasks->isNotEmpty() ? 'book-open' : 'rocket-launch'), 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2 inline')
                {{ __(Auth::user()->backupTasks->isNotEmpty() ? 'Overview' : 'Steps to Get Started') }}
            </x-responsive-nav-link>

            @if (Auth::user()->backupTasks->isNotEmpty())
                <x-responsive-nav-link :href="route('backup-tasks.index')" :active="request()->routeIs('backup-tasks.*')" wire:navigate>
                    @svg('heroicon-o-archive-box', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2 inline')
                    {{ __('Backup Tasks') }}
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->backupDestinations->isNotEmpty())
                <x-responsive-nav-link :href="route('backup-destinations.index')" :active="request()->routeIs('backup-destinations.*')" wire:navigate>
                    @svg('heroicon-o-globe-europe-africa', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2 inline')
                    {{ __('Backup Destinations') }}
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->remoteServers->isNotEmpty())
                <x-responsive-nav-link :href="route('remote-servers.index')" :active="request()->routeIs('remote-servers.*')" wire:navigate>
                    @svg('heroicon-o-server-stack', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2 inline')
                    {{ __('Remote Servers') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="py-4 border-t-2 border-white">
            <div class="px-4">
                <button @click="userOpen = !userOpen" class="flex items-center w-full text-left bg-gray-800 rounded-lg p-3 hover:bg-gray-700 transition-colors duration-200">
                    <img class="h-10 w-10 rounded-full mr-3 border border-gray-950"
                         src="{{ Auth::user()->gravatar() }}"
                         alt="{{ Auth::user()->name }}"/>
                    <div>
                        <div class="font-medium text-base text-gray-100"
                             x-data="{ name: @js(auth()->user()->name) }" x-text="name"
                             x-on:profile-updated.window="name = $event.detail.name"></div>
                        <div class="text-sm text-gray-400">{{ __('Click to view options') }}</div>
                    </div>
                    <svg class="ml-auto h-5 w-5 text-gray-400 transition-transform duration-200 ease-in-out"
                         viewBox="0 0 20 20"
                         fill="currentColor"
                         :class="{'rotate-180': userOpen}">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div
                x-show="userOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="mt-3 space-y-1"
            >
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('tags.index')" wire:navigate>
                    {{ __('Tags') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('notification-streams.index')" wire:navigate>
                    {{ __('Notification Streams') }}
                </x-responsive-nav-link>

                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link href="{{ url('/pulse') }}">
                        Laravel Pulse
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ url('/horizon/dashboard') }}">
                        Laravel Horizon
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>

                <!-- Responsive Theme Switcher -->
                <div class="px-4 py-2">
                    <x-responsive-theme-switcher />
                </div>
            </div>
        </div>
    </div>
</nav>
