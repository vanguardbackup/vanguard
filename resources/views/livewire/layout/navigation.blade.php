<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $isMobileMenuOpen = false;
    public bool $isUserDropdownOpen = false;

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }

    public function toggleMobileMenu(): void
    {
        $this->isMobileMenuOpen = !$this->isMobileMenuOpen;
    }

    public function toggleUserDropdown(): void
    {
        $this->isUserDropdownOpen = !$this->isUserDropdownOpen;
    }
}; ?>

<nav x-data="{
    mobileMenuOpen: false,
    userDropdownOpen: false
}" class="bg-gradient-to-r from-primary-900 to-primary-800 border-b border-primary-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('overview') }}" wire:navigate>
                        <x-application-logo class="block h-8 w-auto text-primary-300" />
                    </a>
                </div>

                <!-- Primary Navigation Menu -->
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <x-nav-link :href="route('overview')" :active="request()->routeIs('overview')" wire:navigate>
                        <x-dynamic-component :component="'heroicon-o-' . (Auth::user()->backupTasks->isNotEmpty() ? 'book-open' : 'rocket-launch')" class="h-5 w-5 mr-2" />
                        {{ __(Auth::user()->backupTasks->isNotEmpty() ? 'Overview' : 'Get Started') }}
                    </x-nav-link>

                    @if (Auth::user()->backupTasks->isNotEmpty())
                        <x-nav-link :href="route('backup-tasks.index')" :active="request()->routeIs('backup-tasks.*')" wire:navigate>
                            <x-heroicon-o-archive-box class="h-5 w-5 mr-2" />
                            {{ __('Backup Tasks') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Secondary Navigation and User Menu -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <div class="flex items-center space-x-4">
                    @if (Auth::user()->backupDestinations->isNotEmpty())
                        <x-nav-link :href="route('backup-destinations.index')" :active="request()->routeIs('backup-destinations.*')" wire:navigate>
                            <x-heroicon-o-globe-europe-africa class="h-5 w-5 mr-2" />
                            {{ __('Destinations') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->remoteServers->isNotEmpty())
                        <x-nav-link :href="route('remote-servers.index')" :active="request()->routeIs('remote-servers.*')" wire:navigate>
                            <x-heroicon-o-server-stack class="h-5 w-5 mr-2" />
                            {{ __('Servers') }}
                        </x-nav-link>
                    @endif
                </div>

                <!-- User Dropdown -->
                <div class="ml-3 relative">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button x-data="{ open: false }" @click="open = !open" class="flex items-center text-sm font-medium text-primary-200 hover:text-primary-100 focus:outline-none transition duration-150 ease-in-out">
                                <div class="mr-3 relative">
                                    <img class="h-8 w-8 rounded-full object-cover" src="{{ Auth::user()->gravatar() }}" alt="{{ Auth::user()->name }}">
                                </div>
                                <span x-data="{ name: @js(auth()->user()->first_name) }" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                                <svg class="ml-2 -mr-0.5 h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')" wire:navigate>
                                <x-heroicon-o-user-circle class="w-5 h-5 mr-2 inline" />
                                {{ __('My Profile') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('notification-streams.index')" wire:navigate>
                                <x-heroicon-o-bell class="w-5 h-5 mr-2 inline" />
                                {{ __('Notifications') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('statistics')" wire:navigate>
                                <x-heroicon-o-chart-pie class="w-5 h-5 mr-2 inline" />
                                {{ __('Statistics') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('profile.api')" wire:navigate>
                                <x-heroicon-o-code-bracket class="w-5 h-5 mr-2 inline" />
                                {{ __('API Tokens') }}
                            </x-dropdown-link>

                            <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>

                            <x-dropdown-link :href="route('profile.mfa')" wire:navigate>
                                <x-heroicon-o-lock-closed class="w-5 h-5 mr-2 inline" />
                                {{ __('Security Settings') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('profile.help')" wire:navigate>
                                <x-heroicon-o-lifebuoy class="w-5 h-5 mr-2 inline" />
                                {{ __('Get Help') }}
                            </x-dropdown-link>

                            @if (Auth::user()->isAdmin())
                                <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>

                                <x-dropdown-link href="{{ url('/pulse') }}">
                                    <x-heroicon-o-chart-bar class="w-5 h-5 mr-2 inline" />
                                    {{ __('Laravel Pulse') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ url('/horizon/dashboard') }}">
                                    <x-heroicon-o-cpu-chip class="w-5 h-5 mr-2 inline" />
                                    {{ __('Laravel Horizon') }}
                                </x-dropdown-link>
                            @endif

                            <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>

                            <button wire:click="logout" class="w-full text-start" role="menuitem">
                                <x-dropdown-link>
                                    @svg('heroicon-o-arrow-left-on-rectangle', 'h-5 w-5 mr-2 inline')
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Theme Switcher -->
                <div class="ml-3">
                    <x-theme-switcher />
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center sm:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="inline-flex items-center justify-center p-2 rounded-md text-primary-300 hover:text-primary-100 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="block h-6 w-6" :class="{'hidden': mobileMenuOpen, 'block': !mobileMenuOpen }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="hidden h-6 w-6" :class="{'block': mobileMenuOpen, 'hidden': !mobileMenuOpen }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu, show/hide based on menu state. -->
    <div x-show="mobileMenuOpen" class="sm:hidden" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('overview')" :active="request()->routeIs('overview')" wire:navigate>
                <x-dynamic-component :component="'heroicon-o-' . (Auth::user()->backupTasks->isNotEmpty() ? 'book-open' : 'rocket-launch')" class="h-5 w-5 mr-2 inline" />
                {{ __(Auth::user()->backupTasks->isNotEmpty() ? 'Overview' : 'Get Started') }}
            </x-responsive-nav-link>

            @if (Auth::user()->backupTasks->isNotEmpty())
                <x-responsive-nav-link :href="route('backup-tasks.index')" :active="request()->routeIs('backup-tasks.*')" wire:navigate>
                    <x-heroicon-o-archive-box class="h-5 w-5 mr-2 inline" />
                    {{ __('Backup Tasks') }}
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->backupDestinations->isNotEmpty())
                <x-responsive-nav-link :href="route('backup-destinations.index')" :active="request()->routeIs('backup-destinations.*')" wire:navigate>
                    <x-heroicon-o-globe-europe-africa class="h-5 w-5 mr-2 inline" />
                    {{ __('Destinations') }}
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->remoteServers->isNotEmpty())
                <x-responsive-nav-link :href="route('remote-servers.index')" :active="request()->routeIs('remote-servers.*')" wire:navigate>
                    <x-heroicon-o-server-stack class="h-5 w-5 mr-2 inline" />
                    {{ __('Servers') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-primary-700">
            <div class="flex items-center px-4">
                <div class="flex-shrink-0">
                    <img class="h-10 w-10 rounded-full" src="{{ Auth::user()->gravatar() }}" alt="{{ Auth::user()->name }}">
                </div>
                <div class="ml-3">
                    <div class="text-base font-medium text-primary-100" x-data="{ name: @js(auth()->user()->name) }" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="text-sm font-medium text-primary-300">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    <x-heroicon-o-user-circle class="w-5 h-5 mr-2 inline" />
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('tags.index')" wire:navigate>
                    <x-heroicon-o-tag class="w-5 h-5 mr-2 inline" />
                    {{ __('Tags') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notification-streams.index')" wire:navigate>
                    <x-heroicon-o-bell class="w-5 h-5 mr-2 inline" />
                    {{ __('Notifications') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.api')" wire:navigate>
                    <x-heroicon-o-code-bracket class="w-5 h-5 mr-2 inline" />
                    {{ __('API Tokens') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.mfa')" wire:navigate>
                    <x-heroicon-o-lock-closed class="w-5 h-5 mr-2 inline" />
                    {{ __('2FA Settings') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('statistics')" wire:navigate>
                    <x-heroicon-o-chart-pie class="w-5 h-5 mr-2 inline" />
                    {{ __('Statistics') }}
                </x-responsive-nav-link>
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link href="{{ url('/pulse') }}">
                        <x-heroicon-o-chart-bar class="w-5 h-5 mr-2 inline" />
                        {{ __('Laravel Pulse') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ url('/horizon/dashboard') }}">
                        <x-heroicon-o-cpu-chip class="w-5 h-5 mr-2 inline" />
                        {{ __('Laravel Horizon') }}
                    </x-responsive-nav-link>
                @endif
            </div>
            <button wire:click="logout" class="w-full text-start">
                <x-responsive-nav-link>
                    @svg('heroicon-o-arrow-left-on-rectangle', 'h-5 w-5 text-gray-50 mr-2 inline')
                    {{ __('Log Out') }}
                </x-responsive-nav-link>
            </button>
            <div class="mt-3 px-4">
                <x-responsive-theme-switcher />
            </div>
        </div>
    </div>
</nav>
