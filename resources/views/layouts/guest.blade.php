@props(['title' => null, 'description' => null])
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100 dark:bg-gray-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="keywords" content="Vanguard, backup solution, open-source, server backup, application backup, Laravel">
    <meta name="robots" content="index, follow">
    <meta name="author" content="{{ config('app.name', 'Vanguard') }}">
    <meta name="theme-color" content="#000000">

    @php
        $siteTitle = config('app.name', 'Vanguard');
        $siteDescription = 'Empower your Laravel projects with Vanguard - the robust, open-source backup solution.';
        $pageTitle = $title ? "$title - $siteTitle" : $siteTitle;
        $pageDescription = $description ?? $siteDescription;
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ asset('og-image.jpg') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="{{ $pageTitle }}">
    <meta property="twitter:description" content="{{ $pageDescription }}">
    <meta property="twitter:image" content="{{ asset('twitter-image.jpg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Poppins:300,400,500,600,700&display=swap" rel="stylesheet"/>

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

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .animate-rise {
            opacity: 0;
            transform: translateY(5px);
            animation: rise 1.5s ease-out forwards;
        }

        @keyframes rise {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stagger-animation > * {
            opacity: 0;
            animation: staggerFade 1s ease-out forwards;
        }

        .stagger-animation > *:nth-child(1) { animation-delay: 0.2s; }
        .stagger-animation > *:nth-child(2) { animation-delay: 0.4s; }
        .stagger-animation > *:nth-child(3) { animation-delay: 0.6s; }

        @keyframes staggerFade {
            to {
                opacity: 1;
            }
        }

        .hover-lift {
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }

        .hover-lift:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .animate-float {
            animation: gentleFloat 30s ease-in-out infinite;
        }

        @keyframes gentleFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
    </style>
</head>
<body class="font-sans text-gray-900 dark:text-gray-100 antialiased h-full">
<div class="flex min-h-screen">
    <!-- Left side: Form half -->
    <div class="flex flex-1 flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <div class="text-center">
                <a href="/" wire:navigate class="inline-block hover-lift">
                    <x-application-logo class="w-44 h-auto fill-current text-primary-900 dark:text-primary-400"/>
                </a>
                @isset($title)
                    <h2 class="mt-6 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                        {{ $title }}
                    </h2>
                @endisset
                @isset($description)
                    <p class="mt-2 text-sm font-light text-gray-600 dark:text-gray-400">
                        {{ $description }}
                    </p>
                @endisset
            </div>
            <div class="mt-8">
                {{ $slot }}
            </div>
        </div>
    </div>

    <!-- Right side: Vanguard showcase half -->
    <div
        class="relative hidden w-0 flex-1 lg:block bg-gradient-to-br from-gray-900 via-primary-900 to-gray-900 overflow-hidden">
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-full h-full absolute">
                <div
                    class="animate-float absolute top-1/4 left-1/4 w-64 h-64 bg-primary-500 rounded-full mix-blend-multiply filter blur-xl opacity-30"></div>
                <div
                    class="animate-float absolute bottom-1/4 right-1/4 w-64 h-64 bg-secondary-500 rounded-full mix-blend-multiply filter blur-xl opacity-30"></div>
                <div
                    class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-accent-500 rounded-full mix-blend-multiply filter blur-xl opacity-30"></div>
            </div>
            <div class="max-w-2xl mx-auto px-8 py-12 relative z-10">
                <div>
                    <h2 class="text-5xl font-semibold mb-6 text-white">Vanguard Backup</h2>
                    <p class="text-xl font-light mb-12 text-gray-300"> Open-source backup solution for server and
                        application backup, built by the community for the community. </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12 stagger-animation">
                    <div class="bg-white bg-opacity-10 rounded-lg p-6 hover-lift">
                        <h3 class="text-2xl font-medium mb-4 text-white flex items-center">
                            @svg('hugeicons-zap', 'w-6 h-6 mr-2 text-primary-400')
                            Key Features
                        </h3>
                        <ul class="space-y-2 text-gray-300 font-light">
                            <li class="flex items-center">
                                @svg('hugeicons-tick-01', 'w-5 h-5 mr-2 text-green-400')
                                Automated backups
                            </li>
                            <li class="flex items-center">
                                @svg('hugeicons-tick-01', 'w-5 h-5 mr-2 text-green-400')
                                Multiple storage options
                            </li>
                            <li class="flex items-center">
                                @svg('hugeicons-tick-01', 'w-5 h-5 mr-2 text-green-400')
                                Support for Laravel
                            </li>
                            <li class="flex items-center">
                                @svg('hugeicons-tick-01', 'w-5 h-5 mr-2 text-green-400')
                                Powerful scheduling
                            </li>
                        </ul>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-lg p-6 hover-lift">
                        <h3 class="text-2xl font-medium mb-4 text-white flex items-center">
                            @svg('hugeicons-user-group', 'w-6 h-6 mr-2 text-primary-400')
                            Community
                        </h3>
                        <ul class="space-y-2 text-gray-300 font-light">
                            <li class="flex items-center">
                                @svg('hugeicons-user-group', 'w-5 h-5 mr-2 text-white')
                                Active contributors
                            </li>
                            <li class="flex items-center">
                                @svg('hugeicons-calendar-02', 'w-5 h-5 mr-2 text-white')
                                Regular updates
                            </li>
                            <li class="flex items-center">
                                @svg('hugeicons-github', 'w-5 h-5 mr-2 text-white')
                                GitHub discussions
                            </li>
                            <li class="flex items-center">
                                @svg('hugeicons-source-code-square', 'w-5 h-5 mr-2 text-white')
                                Official SDKs
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mb-16 animate-rise">
                    <h3 class="text-3xl font-semibold mb-6 text-white flex items-center justify-center sm:justify-start">
                       @svg('hugeicons-puzzle', 'w-8 h-8 mr-3 text-primary-400')
                        Get Involved
                    </h3>
                    <p class="mb-8 text-xl font-light text-gray-300 text-center sm:text-left">Join our open-source
                        community and contribute to better backup solutions!</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 stagger-animation">
                        <a href="https://github.com/vanguardbackup/vanguard" target="_blank" rel="noopener noreferrer"
                           class="group bg-white text-gray-900 hover:bg-gray-100 p-6 rounded-xl transition duration-300 hover-lift flex flex-col items-center text-center">
                            @svg('hugeicons-github', 'w-12 h-12 mb-4 text-gray-700 group-hover:text-black')
                            <h4 class="text-xl font-medium mb-2">GitHub Repo</h4>
                            <p class="text-gray-600 group-hover:text-gray-800 font-light">Explore our code and
                                contribute</p>
                        </a>
                        <a href="https://vanguardbackup.com" target="_blank"
                           class="group bg-gradient-to-br from-primary-500 to-primary-700 text-white p-6 rounded-xl transition duration-300 hover-lift flex flex-col items-center text-center">
                            @svg('hugeicons-global', 'w-12 h-12 mb-4')
                            <h4 class="text-xl font-medium mb-2">Website</h4>
                            <p class="text-gray-100 group-hover:text-white font-light">Learn more about Vanguard</p>
                        </a>
                        <a href="https://docs.vanguardbackup.com" target="_blank"
                           class="group bg-gradient-to-br from-primary-500 to-primary-700 text-white p-6 rounded-xl transition duration-300 hover-lift flex flex-col items-center text-center">
                            @svg('hugeicons-book-open-02', 'w-12 h-12 mb-4')
                            <h4 class="text-xl font-medium mb-2">Documentation</h4>
                            <p class="text-gray-100 group-hover:text-white font-light">Read guides and our FAQ</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
