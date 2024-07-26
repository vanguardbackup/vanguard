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
@if (!ssh_keys_exist() || !config('app.ssh.passphrase'))
    <div x-data="{ show: true, copied: false, showEnvHelp: false, showFull: false }" x-show="show" class="bg-gradient-to-r from-red-600 to-red-700 text-white">
        <div class="max-w-7xl mx-auto py-2 px-2 sm:px-4 md:px-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
                <div class="flex-1 flex items-center mb-2 sm:mb-0">
                    <span class="flex p-1 sm:p-2 rounded-lg bg-red-800">
                        @svg('heroicon-o-exclamation-triangle', 'h-4 w-4 sm:h-5 sm:w-5 text-white')
                    </span>
                    <p class="ml-2 sm:ml-3 text-sm sm:text-base font-medium">
                        @if (!ssh_keys_exist())
                            {{ __('Warning! SSH key missing.') }}
                        @else
                            {{ __('Warning! SSH passphrase not set.') }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row items-center sm:items-center space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
                    @if (!ssh_keys_exist())
                        <div class="relative w-full sm:w-auto">
                            <button @click="showFull = !showFull" class="w-full sm:w-auto flex items-center justify-center px-3 py-1 border border-transparent rounded-md text-xs sm:text-sm leading-5 font-medium text-white bg-red-800 hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-red-700 focus:ring-white transition ease-in-out duration-150">
                                <span x-show="!showFull">{{ __('Show Command') }}</span>
                                <span x-show="showFull">{{ __('Hide Command') }}</span>
                            </button>
                            <div x-show="showFull" @click.away="showFull = false" class="origin-top-right absolute right-0 mt-2 w-full sm:w-72 rounded-md shadow-lg z-10">
                                <div class="rounded-md bg-white shadow-xs p-3 sm:p-4">
                                    <div class="flex items-center justify-between bg-gray-100 p-2 rounded">
                                        <code id="command" class="text-xs sm:text-sm text-gray-800 font-mono break-all sm:break-normal">php artisan vanguard:generate-ssh-key</code>
                                        <button
                                            @click="
                                                navigator.clipboard.writeText(document.getElementById('command').textContent);
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                            class="ml-2 text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                            :title="copied ? '{{ __('Copied!') }}' : '{{ __('Copy') }}"
                                        >
                                            <span x-show="!copied">
                                                @svg('heroicon-o-clipboard-document', 'h-4 w-4 sm:h-5 sm:w-5')
                                            </span>
                                            <span x-show="copied" x-cloak>
                                                @svg('heroicon-o-clipboard-document-check', 'h-4 w-4 sm:h-5 sm:w-5')
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="w-full sm:w-auto flex justify-center">
                            @livewire('other.generate-ssh-keys-button')
                        </div>
                    @else
                        <div class="relative w-full sm:w-auto">
                            <button @click="showEnvHelp = !showEnvHelp" class="w-full sm:w-auto flex items-center justify-center px-3 py-1 border border-transparent rounded-md text-xs sm:text-sm leading-5 font-medium text-white bg-red-800 hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-red-700 focus:ring-white transition ease-in-out duration-150">
                                {{ __('How to Set Passphrase') }}
                            </button>
                            <div x-show="showEnvHelp" @click.away="showEnvHelp = false" class="origin-top-right absolute right-0 mt-2 w-full sm:w-80 rounded-md shadow-lg z-10">
                                <div class="rounded-md bg-white text-gray-800 shadow-xs p-3 sm:p-4">
                                    <p class="text-xs sm:text-sm mb-2">{{ __('To set the SSH passphrase:') }}</p>
                                    <ol class="list-decimal list-inside text-xs sm:text-sm space-y-1">
                                        <li>{{ __('Open your .env file') }}</li>
                                        <li>{{ __('Add or update the following line:') }}</li>
                                        <code class="block bg-gray-100 p-2 rounded mt-1 text-xs break-all">SSH_PASSPHRASE=your_passphrase_here</code>
                                        <li>{{ __('Save the file and restart your application') }}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="absolute top-1 right-1 sm:relative sm:top-auto sm:right-auto sm:ml-2">
                    <button
                        type="button"
                        @click="show = false"
                        class="p-1 rounded-md hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-white"
                    >
                        @svg('heroicon-o-x-mark', 'h-4 w-4 sm:h-5 sm:w-5')
                    </button>
                </div>
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
