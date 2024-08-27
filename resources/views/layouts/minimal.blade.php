<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>Two-Factor Authentication | {{ config('app.name') }}</title>

        <!-- Basic Metadata -->
        <meta
            name="description"
            content="{{ config('app.name') }} - Open-source backup solution for servers and applications"
        />

        <!-- Open Graph / Discord -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:title" content="{{ config('app.name') }} - Two-Factor Authentication" />
        <meta property="og:description" content="Open-source backup solution for servers and applications" />
        <meta property="og:image" content="{{ asset('og-image.jpg') }}" />

        <!-- Theme Colour -->
        <meta name="theme-color" content="#000000" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=Poppins:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Icon -->

        @if (config('app.env') === 'local')
            <link rel="icon" href="{{ asset('local-favicon.ico') }}" type="image/x-icon" />
            <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('local-apple-touch-icon.png') }}" />
            <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('local-favicon-32x32.png') }}" />
            <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('local-favicon-16x16.png') }}" />
            <link rel="manifest" href="{{ asset('local-site.webmanifest') }}" />
            <link rel="mask-icon" href="{{ asset('local-safari-pinned-tab.svg') }}" color="#b17a32" />
            <meta name="msapplication-TileColor" content="#b17a32" />
            <meta name="theme-color" content="#ffffff" />
        @else
            <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon" />
            <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
            <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}" />
            <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}" />
            <link rel="manifest" href="{{ asset('site.webmanifest') }}" />
            <link rel="mask-icon" href="{{ asset('safari-pinned-tab.svg') }}" color="#020617" />
            <meta name="msapplication-TileColor" content="#020617" />
            <meta name="theme-color" content="#020617" />
        @endif
    </head>
    <body class="bg-primary-100 font-sans antialiased dark:bg-gray-900">
        <div class="flex min-h-screen flex-col justify-between">
            <main class="flex flex-grow items-center justify-center px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
        <x-toaster-hub />
    </body>
</html>
