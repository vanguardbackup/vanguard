<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public bool $isMobileMenuOpen = false;
    public bool $isUserDropdownOpen = false;

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }

    public function toggleMobileMenu(): void
    {
        $this->isMobileMenuOpen = ! $this->isMobileMenuOpen;
    }

    public function toggleUserDropdown(): void
    {
        $this->isUserDropdownOpen = ! $this->isUserDropdownOpen;
    }

    public function search(): void
    {
        $this->dispatch('toggle-spotlight');
    }
}; ?>

<nav
    x-data="{
        mobileMenuOpen: false,
        userDropdownOpen: false,
    }"
    class="border-b border-primary-700 bg-gradient-to-r from-primary-900 to-primary-800"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="flex flex-shrink-0 items-center">
                    <a href="{{ route('overview') }}" wire:navigate>
                        <x-application-logo class="block h-6 w-auto text-primary-300" />
                    </a>
                </div>

                <!-- Primary Navigation Menu -->
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <x-nav-link :href="route('overview')" :active="request()->routeIs('overview')" wire:navigate>
                        <x-dynamic-component
                            :component="'hugeicons-' . (Auth::user()->backupTasks->isNotEmpty() ? 'book-open-01' : 'rocket-01')"
                            class="mr-2 h-5 w-5"
                        />
                        {{ __(Auth::user()->backupTasks->isNotEmpty() ? 'Overview' : 'Get Started') }}
                    </x-nav-link>

                    @if (Auth::user()->backupTasks->isNotEmpty())
                        <x-nav-link
                            :href="route('backup-tasks.index')"
                            :active="request()->routeIs('backup-tasks.*')"
                            wire:navigate
                        >
                            <x-hugeicons-archive-02 class="mr-2 h-5 w-5" />
                            {{ __('Backup Tasks') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Secondary Navigation and User Menu -->
            <div class="hidden sm:ml-6 sm:flex sm:items-center">
                <div class="flex items-center space-x-4">
                    @if (Auth::user()->backupDestinations->isNotEmpty())
                        <x-nav-link
                            :href="route('backup-destinations.index')"
                            :active="request()->routeIs('backup-destinations.*')"
                            wire:navigate
                        >
                            <x-hugeicons-folder-cloud class="mr-2 h-5 w-5" />
                            {{ __('Destinations') }}
                        </x-nav-link>
                    @endif

                    @if (Auth::user()->remoteServers->isNotEmpty())
                        <x-nav-link
                            :href="route('remote-servers.index')"
                            :active="request()->routeIs('remote-servers.*')"
                            wire:navigate
                        >
                            <x-hugeicons-hard-drive class="mr-2 h-5 w-5" />
                            {{ __('Servers') }}
                        </x-nav-link>
                    @endif

                    <x-nav-link wire:click="search" wire:navigate>
                        <x-hugeicons-search-02 class="mr-2 h-5 w-5" />
                    </x-nav-link>
                </div>

                <!-- User Dropdown -->
                <div class="relative ml-3">
                    <x-dropdown align="right" width="52">
                        <x-slot name="trigger">
                            <button
                                x-data="{ open: false }"
                                @click="open = !open"
                                @click.away="open = false"
                                class="flex items-center text-sm font-medium text-primary-200 transition duration-150 ease-in-out hover:text-primary-100 focus:outline-none"
                            >
                                <div class="relative mr-3">
                                    <img
                                        class="h-8 w-8 rounded-full object-cover"
                                        src="{{ Auth::user()->gravatar() }}"
                                        alt="{{ Auth::user()->name }}"
                                    />
                                </div>
                                <span
                                    x-data="{ name: @js(auth()->user()->first_name) }"
                                    x-text="name"
                                    x-on:profile-updated.window="name = $event.detail.name"
                                ></span>

                                <svg
                                    class="-mr-0.5 ml-2 h-4 w-4 transition-transform duration-200"
                                    :class="{ 'rotate-180': open }"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    width="24"
                                    height="24"
                                    fill="none"
                                >
                                    <path
                                        d="M18 9.00005C18 9.00005 13.5811 15 12 15C10.4188 15 6 9 6 9"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')" wire:navigate class="mt-0.5">
                                <x-hugeicons-user class="mr-2 inline h-5 w-5 text-primary-800 dark:text-white" />
                                {{ __('My Profile') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('notification-streams.index')" wire:navigate>
                                <x-hugeicons-notification-02
                                    class="mr-2 inline h-5 w-5 text-primary-800 dark:text-white"
                                />
                                {{ __('Notifications') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('statistics')" wire:navigate>
                                <x-hugeicons-analytics-01
                                    class="mr-2 inline h-5 w-5 text-primary-800 dark:text-white"
                                />
                                {{ __('Statistics') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('profile.api')" wire:navigate>
                                <x-hugeicons-ticket-02 class="mr-2 inline h-5 w-5 text-primary-800 dark:text-white" />
                                {{ __('API Tokens') }}
                            </x-dropdown-link>

                            <div class="my-1 border-t border-gray-200 dark:border-gray-600"></div>

                            <x-dropdown-link :href="route('profile.mfa')" wire:navigate>
                                <x-hugeicons-square-lock-01
                                    class="mr-2 inline h-5 w-5 text-primary-800 dark:text-white"
                                />
                                {{ __('Security Settings') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('profile.help')" wire:navigate>
                                <x-hugeicons-mentoring class="mr-2 inline h-5 w-5 text-primary-800 dark:text-white" />
                                {{ __('Get Help') }}
                            </x-dropdown-link>

                            <div class="my-1 border-t border-gray-200 dark:border-gray-600"></div>

                            <x-dropdown-link
                                x-data=""
                                @click="$dispatch('open-modal', 'theme-switcher'); $el.closest('.dropdown-menu').classList.add('hidden')"
                            >
                                <x-hugeicons-paint-board class="mr-2 inline h-5 w-5 text-primary-800 dark:text-white" />
                                {{ __('Appearance') }}
                            </x-dropdown-link>

                            @if (Auth::user()->isAdmin())
                                <div class="my-1 border-t border-gray-200 dark:border-gray-600"></div>
                                <x-dropdown-link href="{{ url('/admin/instance-details') }}">
                                    <x-hugeicons-database-locked
                                        class="mr-2 inline h-5 w-5 text-primary-800 dark:text-white"
                                    />
                                    {{ __('Instance Details') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ url('/pulse') }}">
                                    <x-hugeicons-dashboard-browsing
                                        class="mr-2 inline h-5 w-5 text-primary-800 dark:text-white"
                                    />
                                    {{ __('Laravel Pulse') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ url('/horizon/dashboard') }}">
                                    <x-hugeicons-cpu class="mr-2 inline h-5 w-5 text-primary-800 dark:text-white" />
                                    {{ __('Laravel Horizon') }}
                                </x-dropdown-link>
                            @endif

                            <div class="my-1 border-t border-gray-200 dark:border-gray-600"></div>

                            <button wire:click="logout" class="w-full text-start" role="menuitem">
                                <x-dropdown-link>
                                    @svg('hugeicons-logout-03', 'mr-2 inline h-5 w-5 text-primary-800 dark:text-white')
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center sm:hidden">
                <button
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="inline-flex items-center justify-center rounded-md p-2 text-primary-300 hover:bg-primary-700 hover:text-primary-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
                    aria-controls="mobile-menu"
                    aria-expanded="false"
                >
                    <span class="sr-only">Open main menu</span>
                    <svg
                        class="block h-6 w-6"
                        :class="{'hidden': mobileMenuOpen, 'block': !mobileMenuOpen }"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                    <svg
                        class="hidden h-6 w-6"
                        :class="{'block': mobileMenuOpen, 'hidden': !mobileMenuOpen }"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
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

    <!-- Mobile menu, show/hide based on menu state. -->
    <div x-show="mobileMenuOpen" class="sm:hidden" id="mobile-menu">
        <div class="space-y-1 pb-3 pt-2">
            <x-responsive-nav-link :href="route('overview')" :active="request()->routeIs('overview')" wire:navigate>
                <x-dynamic-component
                    :component="'hugeicons-' . (Auth::user()->backupTasks->isNotEmpty() ? 'book-open-01' : 'rocket-01')"
                    class="mr-2 inline h-5 w-5"
                />
                {{ __(Auth::user()->backupTasks->isNotEmpty() ? 'Overview' : 'Get Started') }}
            </x-responsive-nav-link>

            @if (Auth::user()->backupTasks->isNotEmpty())
                <x-responsive-nav-link
                    :href="route('backup-tasks.index')"
                    :active="request()->routeIs('backup-tasks.*')"
                    wire:navigate
                >
                    <x-hugeicons-archive-02 class="mr-2 inline h-5 w-5" />
                    {{ __('Backup Tasks') }}
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->backupDestinations->isNotEmpty())
                <x-responsive-nav-link
                    :href="route('backup-destinations.index')"
                    :active="request()->routeIs('backup-destinations.*')"
                    wire:navigate
                >
                    <x-hugeicons-folder-cloud class="mr-2 inline h-5 w-5" />
                    {{ __('Destinations') }}
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->remoteServers->isNotEmpty())
                <x-responsive-nav-link
                    :href="route('remote-servers.index')"
                    :active="request()->routeIs('remote-servers.*')"
                    wire:navigate
                >
                    <x-hugeicons-hard-drive class="mr-2 inline h-5 w-5" />
                    {{ __('Servers') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link href="#" wire:click="search">
                <x-hugeicons-search-02 class="inline h-5 w-5" />
                {{ __('Search') }}
            </x-responsive-nav-link>
        </div>

        <div class="border-t border-primary-700 pb-1 pt-4">
            <div class="flex items-center px-4">
                <div class="flex-shrink-0">
                    <img
                        class="h-10 w-10 rounded-full"
                        src="{{ Auth::user()->gravatar() }}"
                        alt="{{ Auth::user()->name }}"
                    />
                </div>
                <div class="ml-3">
                    <div
                        class="text-base font-medium text-primary-100"
                        x-data="{ name: @js(auth()->user()->name) }"
                        x-text="name"
                        x-on:profile-updated.window="name = $event.detail.name"
                    ></div>
                    <div class="text-sm font-medium text-primary-300">
                        {{ Auth::user()->email }}
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    <x-hugeicons-user class="mr-2 inline h-5 w-5" />
                    {{ __('My Profile') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('tags.index')" wire:navigate>
                    <x-hugeicons-tags class="mr-2 inline h-5 w-5" />
                    {{ __('Tags') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notification-streams.index')" wire:navigate>
                    <x-hugeicons-notification-02 class="mr-2 inline h-5 w-5" />
                    {{ __('Notifications') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('statistics')" wire:navigate>
                    <x-hugeicons-analytics-01 class="mr-2 inline h-5 w-5" />
                    {{ __('Statistics') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.api')" wire:navigate>
                    <x-hugeicons-ticket-02 class="mr-2 inline h-5 w-5" />
                    {{ __('API Tokens') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.mfa')" wire:navigate>
                    <x-hugeicons-square-lock-01 class="mr-2 inline h-5 w-5" />
                    {{ __('Security Settings') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.help')" wire:navigate>
                    <x-hugeicons-mentoring class="mr-2 inline h-5 w-5" />
                    {{ __('Get Help') }}
                </x-responsive-nav-link>
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link href="{{ url('/admin/instance-details') }}">
                        <x-hugeicons-database-locked class="mr-2 inline h-5 w-5" />
                        {{ __('Instance Details') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ url('/pulse') }}">
                        <x-hugeicons-dashboard-browsing class="mr-2 inline h-5 w-5" />
                        {{ __('Laravel Pulse') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ url('/horizon/dashboard') }}">
                        <x-hugeicons-cpu class="mr-2 inline h-5 w-5" />
                        {{ __('Laravel Horizon') }}
                    </x-responsive-nav-link>
                @endif
            </div>
            <button wire:click="logout" class="w-full text-start">
                <x-responsive-nav-link>
                    @svg('hugeicons-logout-03', 'mr-2 inline h-5 w-5 text-gray-50')
                    {{ __('Log Out') }}
                </x-responsive-nav-link>
            </button>
            <div class="mt-3 px-4">
                <x-responsive-theme-switcher />
            </div>
        </div>
    </div>
    <x-theme-switcher />
</nav>
