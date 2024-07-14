<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', '') | {{ config('app.name') }}
    </title>

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
@if (!ssh_keys_exist())
    <div x-data="{ copied: false }">
        <div class="mx-auto text-center bg-red-700/85 border-none text-white px-3 py-2 sm:py-3 rounded relative"
             role="alert">
            <div class="flex flex-col sm:flex-row items-center justify-center sm:space-x-2 space-y-2 sm:space-y-0">
                <div class="flex items-center space-x-1 sm:space-x-2">
                    @svg('heroicon-o-exclamation-triangle', 'h-5 w-5 text-inherit')
                    <strong class="font-bold text-sm">{{ __('Warning!') }}</strong>
                </div>
                <p class="text-sm">
                    <span class="hidden sm:inline">{{ __('Please run') }}</span>
                    <span class="sm:hidden">{{ __('Please run:') }}</span>
                </p>
                <code class="text-xs bg-red-800/60 p-1 sm:px-2 font-medium rounded-lg inline-flex items-center">
                    <span id="command">php artisan vanguard:generate-ssh-key</span>
                    <button
                        title="{{ __('Copy') }}"
                        x-on:click="
                        navigator.clipboard.writeText(document.getElementById('command').textContent);
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                        class="ml-1 focus:outline-none"
                    >
                    <span x-show="!copied">
                        @svg('heroicon-o-clipboard-document', 'h-3 w-3 sm:h-4 sm:w-4')
                    </span>
                        <span x-show="copied" x-cloak>
                        @svg('heroicon-o-clipboard-document-check', 'h-3 w-3 sm:h-4 sm:w-4')
                    </span>
                    </button>
                </code>
                <p class="text-sm hidden sm:block">
                    {{ __('to create your SSH key.') }}
                </p>
                <div class="sm:hidden text-sm">
                    {{ __('to create your SSH key.') }}
                </div>
                <div class="sm:hidden">
                    @livewire('other.generate-ssh-keys-button')
                </div>
            </div>
            <div class="hidden sm:block mt-2">
                @livewire('other.generate-ssh-keys-button')
            </div>
        </div>
    </div>
@endif
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
<footer class="py-1.5 text-xs font-medium bg-primary-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between">
            <div>
                {{ __(':app - v:version', ['app' => config('app.name'),'version' => obtain_vanguard_version()]) }}
                @if (config('app.env') === 'local')
                    <strong class="text-purple-500 uppercase ml-1">{{ __('Local Environment') }}</strong>
                @endif
            </div>
            <div>
                <a href="https://github.com/vanguardbackup/vanguard" title="{{ __('GitHub repository') }}" target="_blank">
                    <x-icons.github class="h-4 w-4 fill-current ease-in-out hover:text-gray-700 dark:hover:text-gray-200 duration-150" />
                </a>
            </div>
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
