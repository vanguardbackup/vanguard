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
    <div x-data="{ show: true, copied: false, showEnvHelp: false, showFull: false }" x-show="show" class="bg-gradient-to-r from-red-600 to-red-700 text-white relative">
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
                            <div x-show="showFull"
                                 @click.away="showFull = false"
                                 x-trap.noscroll="showFull"
                                 class="fixed inset-0 z-50 overflow-y-auto"
                                 aria-labelledby="modal-title"
                                 role="dialog"
                                 aria-modal="true">
                                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                        <div class="px-4 pt-5 pb-4 sm:p-6">
                                            <div class="sm:flex sm:items-start">
                                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-gray-950 dark:bg-gray-50 sm:mx-0 sm:h-8 sm:w-8">
                                                    @svg('heroicon-o-command-line', 'h-6 w-6 text-white dark:text-gray-900')
                                                </div>
                                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                                        {{ __('SSH Key Generation Command') }}
                                                    </h3>
                                                    <div class="mt-2">
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ __('Run this command in your terminal to generate SSH keys:') }}
                                                        </p>
                                                        <div class="mt-3 relative">
                                                            <div class="bg-gray-100 dark:bg-gray-700 rounded-md p-3 font-mono text-sm text-gray-800 dark:text-gray-200 break-all sm:break-normal">
                                                                <code id="command">php artisan vanguard:generate-ssh-key</code>
                                                            </div>
                                                            <button
                                                                @click="
                                navigator.clipboard.writeText(document.getElementById('command').textContent);
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            "
                                                                class="absolute top-2 right-2 p-1 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800"
                                                                :title="copied ? '{{ __('Copied!') }}' : '{{ __('Copy to Clipboard') }}'"
                                                            >
                            <span x-show="!copied">
                                @svg('heroicon-o-clipboard-document', 'h-5 w-5')
                            </span>
                                                                <span x-show="copied" x-cloak>
                                @svg('heroicon-o-clipboard-document-check', 'h-5 w-5')
                            </span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                            <x-secondary-button type="button" @click="showFull = false" centered>
                                                {{ __('Close') }}
                                            </x-secondary-button>
                                        </div>
                                    </div>
                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                            <button type="button"
                                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                                    @click="showFull = false">
                                                {{ __('Close') }}
                                            </button>
                                        </div>
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
                <a href="https://opensource.org/licenses/MIT" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300" target="_blank" rel="noopener noreferrer">{{ __('MIT License') }}</a>.
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
