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
        <link rel="icon" href="{{ asset('local_favicon.ico') }}" type="image/x-icon"/>
        <link rel="shortcut icon" href="{{ asset('local_favicon.png') }}" type="image/x-icon"/>
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
        <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/x-icon"/>
    @endif
</head>
<body class="font-sans antialiased">
@if (!ssh_keys_exist())
    <div>
        <div class="mx-auto text-center bg-red-700/85 border-none text-white px-3 py-5 rounded relative"
             role="alert">
            @svg('heroicon-o-exclamation-triangle', 'h-6 w-6 text-inherit inline mr-1')
            <strong class="font-bold">{{ __('Warning!') }}</strong>
            <span class="block sm:inline">
                    {{ __('Please run') }}
                    <code class="text-sm bg-red-800/60 p-1 mx-1.5 font-medium rounded-lg">
                        php artisan vanguard:generate-ssh-key
                    </code>
                    {{ __('to create your SSH key.') }}
                </span>
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
                <x-application-logo class="w-6 h-6 fill-current text-gray-500 dark:text-gray-400 inline" />
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
</body>
</html>
