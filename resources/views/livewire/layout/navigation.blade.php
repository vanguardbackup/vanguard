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
    open: @entangle('isMobileMenuOpen'),
    userOpen: @entangle('isUserDropdownOpen'),
    desktopDropdownOpen: false
}" class="bg-primary-950 border-b border-gray-900 sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo and Primary Navigation -->
            <div class="flex items-center justify-between w-full md:w-auto">
                <div class="md:hidden flex-grow"></div>
                <a href="{{ route('overview') }}" wire:navigate class="flex-shrink-0 flex items-center mx-auto md:mx-0 group">
                    <x-application-logo class="block h-6 w-auto fill-current text-white transition duration-300 ease-in-out transform mt-0.5"/>
                </a>
                <div class="hidden md:ml-6 md:flex md:space-x-1">
                    <x-nav-link :href="route('overview')" :active="request()->routeIs('overview')" wire:navigate>
                        @svg('heroicon-o-' . (Auth::user()->backupTasks->isNotEmpty() ? 'book-open' : 'rocket-launch'), 'h-5 w-5 mr-2')
                        <span>{{ __(Auth::user()->backupTasks->isNotEmpty() ? 'Overview' : 'Get Started') }}</span>
                    </x-nav-link>
                    @if (Auth::user()->backupTasks->isNotEmpty())
                        <x-nav-link :href="route('backup-tasks.index')" :active="request()->routeIs('backup-tasks.*')" wire:navigate>
                            @svg('heroicon-o-archive-box', 'h-5 w-5 mr-2')
                            <span>{{ __('Backup Tasks') }}</span>
                        </x-nav-link>
                    @endif
                </div>
                <div class="md:hidden flex-grow flex justify-end">
                    <!-- Mobile menu button -->
                    <button wire:click="toggleMobileMenu" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out" aria-label="Toggle mobile menu">
                        <span class="sr-only">Open main menu</span>
                        <svg class="h-6 w-6 transition-opacity duration-200 ease-in-out" :class="{'opacity-0': open, 'opacity-100': !open}" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="h-6 w-6 transition-opacity duration-200 ease-in-out absolute" :class="{'opacity-100': open, 'opacity-0': !open}" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Secondary Navigation and User Menu -->
            <div class="hidden md:flex md:items-center md:space-x-4">
                <div class="flex space-x-1">
                    @if (Auth::user()->backupDestinations->isNotEmpty())
                        <x-nav-link :href="route('backup-destinations.index')" :active="request()->routeIs('backup-destinations.*')" wire:navigate>
                            @svg('heroicon-o-globe-europe-africa', 'h-5 w-5 mr-2')
                            <span>{{ __('Destinations') }}</span>
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->remoteServers->isNotEmpty())
                        <x-nav-link :href="route('remote-servers.index')" :active="request()->routeIs('remote-servers.*')" wire:navigate>
                            @svg('heroicon-o-server-stack', 'h-5 w-5 mr-2')
                            <span>{{ __('Servers') }}</span>
                        </x-nav-link>
                    @endif
                </div>
                <div class="flex items-center space-x-4 ml-4 border-l border-gray-700 pl-4">
                    <x-theme-switcher />
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button @click="desktopDropdownOpen = !desktopDropdownOpen"
                                    class="flex items-center text-sm font-medium text-gray-50 hover:text-gray-100 focus:outline-none transition duration-150 ease-in-out"
                                    aria-expanded="false">
                                <span class="sr-only">Open user menu</span>
                                <div x-data="{
                imageLoaded: false,
                reloadCount: 0,
                maxReloads: 3,
                imageUrl: '{{ Auth::user()->gravatar() }}'
            }"
                                     class="relative h-8 w-8 mr-2">
                                    <!-- Skeleton loader -->
                                    <div
                                        x-show="!imageLoaded"
                                        class="absolute inset-0 bg-gray-600 dark:bg-gray-400 rounded-full animate-pulse"
                                    ></div>
                                    <!-- User avatar -->
                                    <img
                                        x-on:load="imageLoaded = true"
                                        x-on:error="reloadImage"
                                        x-bind:class="{ 'opacity-0': !imageLoaded, 'opacity-100': imageLoaded }"
                                        class="h-8 w-8 rounded-full border border-gray-950 transition-opacity duration-300"
                                        x-bind:src="imageUrl"
                                    />
                                </div>
                                <span x-data="{ name: @js(auth()->user()->first_name) }"
                                      x-text="name"
                                      x-on:profile-updated.window="name = $event.detail.name"
                                ></span>
                                <svg class="ml-2 h-4 w-4 transition-transform duration-200 ease-in-out"
                                     :class="{'rotate-180': desktopDropdownOpen}"
                                     xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20"
                                     fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <script>
                            function reloadImage() {
                                if (this.reloadCount < this.maxReloads) {
                                    setTimeout(() => {
                                        this.reloadCount++;
                                        this.imageLoaded = false;
                                        this.imageUrl = '{{ Auth::user()->gravatar() }}' + '?' + new Date().getTime(); // Cache busting
                                    }, 1000); // 1-second delay before retry
                                }
                            }
                        </script>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')" wire:navigate>
                                @svg('heroicon-o-user-circle', 'h-5 w-5 mr-2 inline')
                                {{ __('Profile') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('tags.index')" wire:navigate>
                                @svg('heroicon-o-tag', 'h-5 w-5 mr-2 inline')
                                {{ __('Tags') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('notification-streams.index')" wire:navigate>
                                @svg('heroicon-o-bell', 'h-5 w-5 mr-2 inline')
                                {{ __('Notifications') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('profile.api')" wire:navigate>
                                @svg('heroicon-o-code-bracket', 'h-5 w-5 mr-2 inline')
                                {{ __('API Tokens') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('profile.mfa')" wire:navigate>
                                @svg('heroicon-o-lock-closed', 'h-5 w-5 mr-2 inline')
                                {{ __('2FA Settings') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('statistics')" wire:navigate>
                                @svg('heroicon-o-chart-pie', 'h-5 w-5 mr-2 inline')
                                {{ __('Statistics') }}
                            </x-dropdown-link>
                            @if (Auth::user()->isAdmin())
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <x-dropdown-link href="{{ url('/pulse') }}">
                                    @svg('heroicon-o-chart-bar', 'h-5 w-5 mr-2 inline')
                                    Laravel Pulse
                                </x-dropdown-link>
                                <x-dropdown-link href="{{ url('/horizon/dashboard') }}">
                                    @svg('heroicon-o-cpu-chip', 'h-5 w-5 mr-2 inline')
                                    Laravel Horizon
                                </x-dropdown-link>
                            @endif
                            <div class="border-t border-gray-200 dark:border-gray-600"></div>
                            <button wire:click="logout" class="w-full text-start" role="menuitem">
                                <x-dropdown-link>
                                    @svg('heroicon-o-arrow-left-on-rectangle', 'h-5 w-5 mr-2 inline')
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2" class="md:hidden" x-trap="open">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('overview')" :active="request()->routeIs('overview')" wire:navigate>
                @svg('heroicon-o-' . (Auth::user()->backupTasks->isNotEmpty() ? 'book-open' : 'rocket-launch'), 'h-5 w-5 text-gray-50 mr-2 inline')
                {{ __(Auth::user()->backupTasks->isNotEmpty() ? 'Overview' : 'Get Started') }}
            </x-responsive-nav-link>
            @if (Auth::user()->backupTasks->isNotEmpty())
                <x-responsive-nav-link :href="route('backup-tasks.index')" :active="request()->routeIs('backup-tasks.*')" wire:navigate>
                    @svg('heroicon-o-archive-box', 'h-5 w-5 text-gray-50 mr-2 inline')
                    {{ __('Backup Tasks') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->backupDestinations->isNotEmpty())
                <x-responsive-nav-link :href="route('backup-destinations.index')" :active="request()->routeIs('backup-destinations.*')" wire:navigate>
                    @svg('heroicon-o-globe-europe-africa', 'h-5 w-5 text-gray-50 mr-2 inline')
                    {{ __('Destinations') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->remoteServers->isNotEmpty())
                <x-responsive-nav-link :href="route('remote-servers.index')" :active="request()->routeIs('remote-servers.*')" wire:navigate>
                    @svg('heroicon-o-server-stack', 'h-5 w-5 text-gray-50 mr-2 inline')
                    {{ __('Servers') }}
                </x-responsive-nav-link>
            @endif
        </div>
        <div class="pt-4 pb-3 border-t border-gray-700">
            <div class="flex items-center px-5">
                <div class="flex-shrink-0">
                    <img class="h-10 w-10 rounded-full" src="{{ Auth::user()->gravatar() }}" alt="{{ Auth::user()->name }}">
                </div>
                <div class="ml-3">
                    <div class="text-base font-medium leading-none text-white" x-data="{ name: @js(auth()->user()->name) }" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="text-sm font-medium leading-none text-gray-400">{{ Auth::user()->email }}</div>
                </div>
            </div>
            <div class="mt-3 px-2 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    @svg('heroicon-o-user-circle', 'h-5 w-5 text-gray-50 mr-2 inline')
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('tags.index')" wire:navigate>
                    @svg('heroicon-o-tag', 'h-5 w-5 text-gray-50 mr-2 inline')
                    {{ __('Tags') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notification-streams.index')" wire:navigate>
                    @svg('heroicon-o-bell', 'h-5 w-5 text-gray-50 mr-2 inline')
                    {{ __('Notifications') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.api')" wire:navigate>
                    @svg('heroicon-o-code-bracket', 'h-5 w-5 text-gray-50 mr-2 inline')
                    {{ __('API Tokens') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.mfa')" wire:navigate>
                    @svg('heroicon-o-lock-closed', 'h-5 w-5 text-gray-50 mr-2 inline')
                    {{ __('2FA Settings') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('statistics')" wire:navigate>
                    @svg('heroicon-o-chart-pie', 'h-5 w-5 text-gray-50 mr-2 inline')
                    {{ __('Statistics') }}
                </x-responsive-nav-link>
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link href="{{ url('/pulse') }}">
                        @svg('heroicon-o-chart-bar', 'h-5 w-5 text-gray-50 mr-2 inline')
                        Laravel Pulse
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ url('/horizon/dashboard') }}">
                        @svg('heroicon-o-cpu-chip', 'h-5 w-5 text-gray-50 mr-2 inline')
                        Laravel Horizon
                    </x-responsive-nav-link>
                @endif
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        @svg('heroicon-o-arrow-left-on-rectangle', 'h-5 w-5 text-gray-50 mr-2 inline')
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
                <div class="px-2 py-2">
                    <x-responsive-theme-switcher />
                </div>
            </div>
        </div>
    </div>
</nav>
