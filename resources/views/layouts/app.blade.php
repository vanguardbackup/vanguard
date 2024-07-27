<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', '') | {{ config('app.name') }}</title>

    <!-- Basic Metadata -->
    <meta name="description" content="{{ config('app.name') }} - Open-source backup solution for servers and applications">

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
@include('partials.missing-keys-and-passphrase')
<div class="min-h-screen bg-primary-100 dark:bg-gray-900">
    <livewire:layout.navigation/>
    {{ Breadcrumbs::render() }}

    <!-- Page Heading -->
    @if (isset($header))
        <header class="bg-white dark:bg-gray-800/50 dark:border-gray-800/30 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endif

    <!-- Page Content -->
    <main>
        {{ $slot }}
    </main>
</div>
<x-toaster-hub/>
<footer class="relative py-6 sm:py-8 text-sm sm:text-base font-medium bg-primary-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400">
    <div class="absolute top-0 left-0 right-0 h-px bg-gray-300 dark:bg-gray-700"></div>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="text-center sm:text-left">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ config('app.name') }}</h2>
                <p class="mb-2">{{ __('Version :version', ['version' => obtain_vanguard_version()]) }}</p>
                <div class="flex flex-wrap justify-center sm:justify-start gap-2 mb-2">
                    @if (config('app.env') === 'local')
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-purple-700 bg-purple-100 rounded-full dark:text-purple-300 dark:bg-purple-900">
                            @svg('heroicon-s-beaker', ['class' => 'h-3 w-3 mr-1'])
                            {{ __('Local') }}
                        </span>
                    @endif
                    @if (config('app.debug'))
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-full dark:text-red-300 dark:bg-red-900">
                            @svg('heroicon-s-bug-ant', ['class' => 'h-3 w-3 mr-1'])
                            {{ __('Debug') }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="text-center sm:text-left">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('Quick Links') }}</h2>
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('profile') }}" class="hover:text-gray-800 dark:hover:text-gray-200 transition duration-150 ease-in-out flex items-center justify-center sm:justify-start">
                            @svg('heroicon-o-user-circle', ['class' => 'h-5 w-5 mr-2'])
                            {{ __('Profile') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('notification-streams.index') }}" class="hover:text-gray-800 dark:hover:text-gray-200 transition duration-150 ease-in-out flex items-center justify-center sm:justify-start">
                            @svg('heroicon-o-bell', ['class' => 'h-5 w-5 mr-2'])
                            {{ __('Notification Streams') }}
                        </a>
                    </li>
                </ul>
            </div>
            <div class="text-center sm:text-left">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('Resources') }}</h2>
                <ul class="space-y-2">
                    <li>
                        <a href="https://docs.vanguardbackup.com" target="_blank" class="hover:text-gray-800 dark:hover:text-gray-200 transition duration-150 ease-in-out flex items-center justify-center sm:justify-start">
                            @svg('heroicon-o-book-open', ['class' => 'h-5 w-5 mr-2'])
                            {{ __('Documentation') }}
                        </a>
                    </li>
                    <li>
                        <a href="https://github.com/vanguardbackup/vanguard" target="_blank" class="hover:text-gray-800 dark:hover:text-gray-200 transition duration-150 ease-in-out flex items-center justify-center sm:justify-start">
                            <x-icons.github class="h-5 w-5 mr-2 fill-current" />
                            GitHub
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="mt-8 pt-6 border-t border-gray-300 dark:border-gray-700 text-center text-xs">
            <p>
                &copy; {{ date('Y') }} {{ config('app.name') }}.
                {{ __('This software is open source and available under the') }}
                <a href="https://opensource.org/licenses/agpl-v3" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300" target="_blank" rel="noopener noreferrer">{{ __('AGPLv3 License') }}</a>.
            </p>
        </div>
    </div>
</footer>
<script>
    document.addEventListener('livewire:navigated', function () {
        new ClipboardJS('.btn');
        document.getElementById('copyButton').addEventListener('click', function () {
            document.getElementById('copiedIcon').classList.remove('hidden');
            document.getElementById('copyIcon').classList.add('hidden');
        });
    });
</script>
</body>
</html>
