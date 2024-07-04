<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-primary-950 border-b border-gray-900">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('overview') }}" wire:navigate>
                        <x-application-logo class="block h-14 w-auto fill-current text-white mt-2"/>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if (Auth::user()->backupTasks->isNotEmpty())
                        <x-nav-link :href="route('overview')" :active="request()->routeIs('overview')" wire:navigate>
                            @svg('heroicon-o-book-open', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2')
                            {{ __('Overview') }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('overview')" :active="request()->routeIs('overview')" wire:navigate>
                            @svg('heroicon-o-rocket-launch', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2')
                            {{ __('Steps to Get Started') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->backupTasks->isNotEmpty())
                        <x-nav-link :href="route('backup-tasks.index')" :active="request()->routeIs('backup-tasks.*')"
                                    wire:navigate>
                            @svg('heroicon-o-archive-box', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2')
                            {{ __('Backup Tasks') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->backupDestinations->isNotEmpty())
                        <x-nav-link :href="route('backup-destinations.index')"
                                    :active="request()->routeIs('backup-destinations.*')" wire:navigate>
                            @svg('heroicon-o-globe-europe-africa', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2')
                            {{ __('Backup Destinations') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->remoteServers->isNotEmpty())
                        <x-nav-link :href="route('remote-servers.index')"
                                    :active="request()->routeIs('remote-servers.*')" wire:navigate>
                            @svg('heroicon-o-server-stack', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2')
                            {{ __('Remote Servers') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-theme-switcher />
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-50 bg-transparent hover:text-gray-100 focus:outline-none transition ease-in-out duration-150">
                            <img class="h-8 w-8 rounded-full mr-2 border border-gray-950"
                                 src="{{ \Illuminate\Support\Facades\Auth::user()->gravatar() }}"
                                 alt="{{ \Illuminate\Support\Facades\Auth::user()->name }}"/>
                            <div x-data="{{ json_encode(['name' => auth()->user()->first_name]) }}" x-text="name"
                                 x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20">
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

                        @if (Auth::user()->isAdmin())
                            <x-dropdown-link href="{{ url('/pulse') }}">
                                {{ __('Laravel Pulse') }}
                            </x-dropdown-link>
                            <x-dropdown-link href="{{ url('/horizon/dashboard') }}">
                                {{ __('Laravel Horizon') }}
                            </x-dropdown-link>
                        @endif

                        <x-dropdown-link :href="route('frequently-asked-questions')" wire:navigate>
                            {{ __('FAQ') }}
                        </x-dropdown-link>

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
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                              stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('remote-servers.index')"
                                   :active="request()->routeIs('remote-servers.*')" wire:navigate>
                @svg('heroicon-o-server-stack', 'h-5 w-5 text-gray-50 dark:text-gray-200 mr-2')
                {{ __('Remote Servers') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200"
                     x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name"
                     x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('tags.index')" wire:navigate>
                    {{ __('Tags') }}
                </x-responsive-nav-link>

                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link href="{{ url('/pulse') }}">
                        {{ __('Laravel Pulse') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ url('/horizon/overview') }}">
                        {{ __('Laravel Horizon') }}
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
