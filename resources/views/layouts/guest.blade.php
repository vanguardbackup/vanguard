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
<div class="flex min-h-full">
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
                            <svg class="w-6 h-6 mr-2 text-primary-400" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Key Features
                        </h3>
                        <ul class="space-y-2 text-gray-300 font-light">
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-400" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 13l4 4L19 7"></path>
                                </svg>
                                Automated backups
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-400" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 13l4 4L19 7"></path>
                                </svg>
                                Multiple storage options
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-400" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 13l4 4L19 7"></path>
                                </svg>
                                Support for Laravel
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-400" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 13l4 4L19 7"></path>
                                </svg>
                                Powerful scheduling
                            </li>
                        </ul>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-lg p-6 hover-lift">
                        <h3 class="text-2xl font-medium mb-4 text-white flex items-center">
                            <svg class="w-6 h-6 mr-2 text-primary-400" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Community
                        </h3>
                        <ul class="space-y-2 text-gray-300 font-light">
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-white" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Active contributors
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-white" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Regular updates
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-white" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                GitHub discussions
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mb-16 animate-rise">
                    <h3 class="text-3xl font-semibold mb-6 text-white flex items-center justify-center sm:justify-start">
                        <svg class="w-8 h-8 mr-3 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path>
                        </svg>
                        Get Involved
                    </h3>
                    <p class="mb-8 text-xl font-light text-gray-300 text-center sm:text-left">Join our open-source
                        community and contribute to better backup solutions!</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 stagger-animation">
                        <a href="https://github.com/vanguardbackup/vanguard" target="_blank" rel="noopener noreferrer"
                           class="group bg-white text-gray-900 hover:bg-gray-100 p-6 rounded-xl transition duration-300 hover-lift flex flex-col items-center text-center">
                            <svg class="w-12 h-12 mb-4 text-gray-700 group-hover:text-black" fill="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                      d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            <h4 class="text-xl font-medium mb-2">GitHub Repo</h4>
                            <p class="text-gray-600 group-hover:text-gray-800 font-light">Explore our code and
                                contribute</p>
                        </a>
                        <a href="https://vanguardbackup.com" target="_blank"
                           class="group bg-gradient-to-br from-primary-500 to-primary-700 text-white p-6 rounded-xl transition duration-300 hover-lift flex flex-col items-center text-center">
                            <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                            <h4 class="text-xl font-medium mb-2">Website</h4>
                            <p class="text-gray-100 group-hover:text-white font-light">Learn more about Vanguard</p>
                        </a>
                        <a href="https://docs.vanguardbackup.com" target="_blank"
                           class="group bg-gradient-to-br from-primary-500 to-primary-700 text-white p-6 rounded-xl transition duration-300 hover-lift flex flex-col items-center text-center">
                            <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
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
