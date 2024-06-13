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
        <div class="mx-auto text-center bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
             role="alert">
            @svg('heroicon-o-exclamation-triangle', 'h-6 w-6 text-red-700 inline mr-1')
            <strong class="font-bold">{{ __('Warning!') }}</strong>
            <span class="block sm:inline">
                    {{ __('Please run') }}
                    <code class="text-sm bg-red-200 p-0.5 mx-1 font-medium">
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
        <header class="bg-white dark:bg-gray-800 shadow">
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
</body>
</html>
