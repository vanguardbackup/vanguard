<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', '') | {{ config('app.name') }}</title>

    <!-- Basic Metadata -->
    <meta name="description"
          content="{{ config('app.name') }} - Open-source backup solution for servers and applications">

    <!-- Open Graph / Discord -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ config('app.name') }}">
    <meta property="og:description" content="Open-source backup solution for servers and applications">
    <meta property="og:image" content="{{ asset('og-image.jpg') }}">

    <!-- Theme Colour -->
    <meta name="theme-color" content="#000000">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Poppins:400,500,600&display=swap" rel="stylesheet"/>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Icon -->
    @if (config('app.env') === 'local')
        <link rel="icon" href="{{ asset('local-favicon.ico') }}" type="image/x-icon"/>
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('local-apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('local-favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('local-favicon-16x16.png') }}">
        <link rel="manifest" href="{{ asset('local-site.webmanifest') }}">
        <link rel="mask-icon" href="{{ asset('local-safari-pinned-tab.svg') }}" color="#b17a32">
        <meta name="msapplication-TileColor" content="#b17a32">
        <meta name="theme-color" content="#ffffff">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <link rel="mask-icon" href="{{ asset('safari-pinned-tab.svg') }}" color="#020617">
        <meta name="msapplication-TileColor" content="#020617">
        <meta name="theme-color" content="#020617">
    @endif
</head>
<body class="font-sans antialiased">
<livewire:other.new-feature-banner/>
@include('partials.missing-keys-and-passphrase')
<div class="min-h-screen bg-primary-100 dark:bg-gray-900">
    <livewire:layout.navigation/>
    @if (session()->has('flash_message'))
        @php
            $flashMessage = session('flash_message');
        @endphp
        <x-partials.flash-message
            :message="$flashMessage['message']"
            :type="$flashMessage['type']"
            :dismissible="$flashMessage['dismissible']"
        />
    @endif
    @include('partials.quiet-mode-banner')
    {{ Breadcrumbs::render() }}
    <!-- Page Heading -->
    @if (isset($header))
        <header>
            <div class="mx-auto">
                <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between min-h-[48px]">
                        <div class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                            {{ $header }}
                        </div>
                        <div class="flex items-center">
                            <div class="hidden md:block">
                                @if (isset($action))
                                    {{ $action }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
    @endif
    <!-- Page Content -->
    <main>
        @if (isset($outsideContainer))
            {{ $outsideContainer }}
        @else
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
                <div class="block md:hidden w-full">
                    @if (isset($action))
                        {{ $action }}
                    @endif
                </div>
                {{ $slot }}
            </div>
        @endif
    </main>
</div>
<x-toaster-hub/>
<footer class="relative py-12 sm:py-16 bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-300">
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
            <div class="text-center sm:text-left">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">{{ config('app.name') }}</h2>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400 font-medium">
                    {{ __('Version :version', ['version' => obtain_vanguard_version()]) }}
                </p>
                <div class="flex flex-wrap justify-center sm:justify-start gap-2 mb-4">
                    @if (config('app.env') === 'local')
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-indigo-700 bg-indigo-100 rounded-full shadow-sm ring-1 ring-indigo-500/20 dark:bg-indigo-900/40 dark:text-indigo-300 dark:ring-indigo-400/30">
                            @svg('hugeicons-laptop', ['class' => 'h-3.5 w-3.5 mr-1.5 text-indigo-500 dark:text-indigo-400'])
                            {{ __('Local') }}
                        </span>
                    @endif
                    @if (config('app.debug'))
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-rose-700 bg-rose-100 rounded-full shadow-sm ring-1 ring-rose-500/20 dark:bg-rose-900/40 dark:text-rose-300 dark:ring-rose-400/30">
                            @svg('hugeicons-bug-01', ['class' => 'h-3.5 w-3.5 mr-1.5 text-rose-500 dark:text-rose-400'])
                            {{ __('Debug') }}
                        </span>
                    @endif
                </div>
            </div>
            @foreach ([
                'Account' => [
                    ['route' => 'profile', 'icon' => 'hugeicons-user', 'label' => __('My Profile')],
                    ['route' => 'notification-streams.index', 'icon' => 'hugeicons-notification-02', 'label' => __('Notifications')],
                    ['route' => 'statistics', 'icon' => 'hugeicons-analytics-01', 'label' => __('Statistics')],
                ],
                'Resources' => [
                    ['url' => 'https://docs.vanguardbackup.com', 'icon' => 'hugeicons-book-02', 'label' => __('Documentation')],
                    ['url' => 'https://github.com/vanguardbackup/vanguard', 'icon' => 'hugeicons-github', 'label' => __('GitHub')],
                    ['route' => 'profile.help', 'icon' => 'hugeicons-mentoring', 'label' => __('Help Center')],
                ],
                'Community' => [
                    ['url' => 'https://github.com/vanguardbackup/vanguard/discussions', 'icon' => 'hugeicons-chatting-01', 'label' => __('Discussions')],
                    ['url' => 'https://github.com/vanguardbackup/vanguard/issues', 'icon' => 'hugeicons-bug-01', 'label' => __('Report an Issue')],
                    ['url' => 'https://github.com/vanguardbackup/vanguard/blob/main/CONTRIBUTING.md', 'icon' => 'hugeicons-agreement-01', 'label' => __('Contribute')],
                ],
            ] as $title => $links)
                <div class="text-center sm:text-left">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __($title) }}</h2>
                    <ul class="space-y-3">
                        @foreach ($links as $link)
                            <li>
                                <a href="{{ isset($link['route']) ? route($link['route']) : $link['url'] }}"
                                   @if (isset($link['url'])) target="_blank" rel="noopener noreferrer" @endif
                                   class="group text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition duration-150 ease-in-out flex items-center justify-center sm:justify-start">
                                    @svg($link['icon'], ['class' => 'h-5 w-5 mr-2 text-gray-400 group-hover:text-primary-500 transition-colors duration-150 ease-in-out'])
                                    <span class="group-hover:underline">{{ $link['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700 text-center text-sm">
            <p class="text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name') }}.
                {{ __('This software is open source and available under the') }}
                <a href="https://opensource.org/licenses/agpl-v3" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 hover:underline" target="_blank" rel="noopener noreferrer">{{ __('AGPLv3 License') }}</a>.
            </p>
        </div>
    </div>
</footer>
</body>
</html>
