@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100 dark:bg-gray-900">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta
            name="keywords"
            content="Vanguard, backup solution, open-source, server backup, application backup, Laravel"
        />
        <meta name="robots" content="index, follow" />
        <meta name="author" content="{{ config('app.name', 'Vanguard') }}" />
        <meta name="theme-color" content="#000000" />

        @php
            $siteTitle = config('app.name', 'Vanguard');
            $siteDescription = 'Empower your Laravel projects with Vanguard - the robust, open-source backup solution.';
            $pageTitle = $title ? "$title - $siteTitle" : $siteTitle;
            $pageDescription = $description ?? $siteDescription;
        @endphp

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:title" content="{{ $pageTitle }}" />
        <meta property="og:description" content="{{ $pageDescription }}" />
        <meta property="og:image" content="{{ asset('og-image.jpg') }}" />

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image" />
        <meta property="twitter:url" content="{{ url()->current() }}" />
        <meta property="twitter:title" content="{{ $pageTitle }}" />
        <meta property="twitter:description" content="{{ $pageDescription }}" />
        <meta property="twitter:image" content="{{ asset('twitter-image.jpg') }}" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=Poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

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

            .stagger-animation > *:nth-child(1) {
                animation-delay: 0.2s;
            }
            .stagger-animation > *:nth-child(2) {
                animation-delay: 0.4s;
            }
            .stagger-animation > *:nth-child(3) {
                animation-delay: 0.6s;
            }

            @keyframes staggerFade {
                to {
                    opacity: 1;
                }
            }

            .hover-lift {
                transition:
                    transform 0.4s ease,
                    box-shadow 0.4s ease;
            }

            .hover-lift:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .animate-float {
                animation: gentleFloat 30s ease-in-out infinite;
            }

            @keyframes gentleFloat {
                0%,
                100% {
                    transform: translateY(0);
                }
                50% {
                    transform: translateY(-3px);
                }
            }
        </style>
    </head>
    <body class="h-full font-sans text-gray-900 antialiased dark:text-gray-100">
        <div class="flex min-h-screen">
            <!-- Left side: Form half -->
            <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
                <div class="mx-auto w-full max-w-sm lg:w-96">
                    <div class="text-center">
                        <a href="/" wire:navigate class="hover-lift inline-block">
                            <x-application-logo
                                class="h-auto w-44 fill-current text-primary-900 dark:text-primary-400"
                            />
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
                class="relative hidden w-0 flex-1 overflow-hidden bg-gradient-to-br from-gray-900 via-primary-900 to-gray-900 lg:block"
            >
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="absolute h-full w-full">
                        <div
                            class="animate-float absolute left-1/4 top-1/4 h-64 w-64 rounded-full bg-primary-500 opacity-30 mix-blend-multiply blur-xl filter"
                        ></div>
                        <div
                            class="animate-float bg-secondary-500 absolute bottom-1/4 right-1/4 h-64 w-64 rounded-full opacity-30 mix-blend-multiply blur-xl filter"
                        ></div>
                        <div
                            class="bg-accent-500 absolute left-1/2 top-1/2 h-96 w-96 -translate-x-1/2 -translate-y-1/2 transform rounded-full opacity-30 mix-blend-multiply blur-xl filter"
                        ></div>
                    </div>
                    <div class="relative z-10 mx-auto max-w-2xl px-8 py-12">
                        <div>
                            <h2 class="mb-6 text-5xl font-semibold text-white">Vanguard Backup</h2>
                            <p class="mb-12 text-xl font-light text-gray-300">
                                Open-source backup solution for server and application backup, built by the community
                                for the community.
                            </p>
                        </div>

                        <div class="stagger-animation mb-12 grid grid-cols-1 gap-8 md:grid-cols-2">
                            <div class="hover-lift rounded-lg bg-white bg-opacity-10 p-6">
                                <h3 class="mb-4 flex items-center text-2xl font-medium text-white">
                                    @svg('hugeicons-zap', 'mr-2 h-6 w-6 text-primary-400')
                                    Key Features
                                </h3>
                                <ul class="space-y-2 font-light text-gray-300">
                                    <li class="flex items-center">
                                        @svg('hugeicons-tick-01', 'mr-2 h-5 w-5 text-green-400')
                                        Automated backups
                                    </li>
                                    <li class="flex items-center">
                                        @svg('hugeicons-tick-01', 'mr-2 h-5 w-5 text-green-400')
                                        Multiple storage options
                                    </li>
                                    <li class="flex items-center">
                                        @svg('hugeicons-tick-01', 'mr-2 h-5 w-5 text-green-400')
                                        Support for Laravel
                                    </li>
                                    <li class="flex items-center">
                                        @svg('hugeicons-tick-01', 'mr-2 h-5 w-5 text-green-400')
                                        Powerful scheduling
                                    </li>
                                </ul>
                            </div>
                            <div class="hover-lift rounded-lg bg-white bg-opacity-10 p-6">
                                <h3 class="mb-4 flex items-center text-2xl font-medium text-white">
                                    @svg('hugeicons-user-group', 'mr-2 h-6 w-6 text-primary-400')
                                    Community
                                </h3>
                                <ul class="space-y-2 font-light text-gray-300">
                                    <li class="flex items-center">
                                        @svg('hugeicons-user-group', 'mr-2 h-5 w-5 text-white')
                                        Active contributors
                                    </li>
                                    <li class="flex items-center">
                                        @svg('hugeicons-calendar-02', 'mr-2 h-5 w-5 text-white')
                                        Regular updates
                                    </li>
                                    <li class="flex items-center">
                                        @svg('hugeicons-github', 'mr-2 h-5 w-5 text-white')
                                        GitHub discussions
                                    </li>
                                    <li class="flex items-center">
                                        @svg('hugeicons-source-code-square', 'mr-2 h-5 w-5 text-white')
                                        Official SDKs
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="animate-rise mb-16">
                            <h3
                                class="mb-6 flex items-center justify-center text-3xl font-semibold text-white sm:justify-start"
                            >
                                @svg('hugeicons-puzzle', 'mr-3 h-8 w-8 text-primary-400')
                                Get Involved
                            </h3>
                            <p class="mb-8 text-center text-xl font-light text-gray-300 sm:text-left">
                                Join our open-source community and contribute to better backup solutions!
                            </p>
                            <div class="stagger-animation grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <a
                                    href="https://github.com/vanguardbackup/vanguard"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="hover-lift group flex flex-col items-center rounded-xl bg-white p-6 text-center text-gray-900 transition duration-300 hover:bg-gray-100"
                                >
                                    @svg('hugeicons-github', 'mb-4 h-12 w-12 text-gray-700 group-hover:text-black')
                                    <h4 class="mb-2 text-xl font-medium">GitHub Repo</h4>
                                    <p class="font-light text-gray-600 group-hover:text-gray-800">
                                        Explore our code and contribute
                                    </p>
                                </a>
                                <a
                                    href="https://vanguardbackup.com"
                                    target="_blank"
                                    class="hover-lift group flex flex-col items-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 p-6 text-center text-white transition duration-300"
                                >
                                    @svg('hugeicons-global', 'mb-4 h-12 w-12')
                                    <h4 class="mb-2 text-xl font-medium">Website</h4>
                                    <p class="font-light text-gray-100 group-hover:text-white">
                                        Learn more about Vanguard
                                    </p>
                                </a>
                                <a
                                    href="https://docs.vanguardbackup.com"
                                    target="_blank"
                                    class="hover-lift group flex flex-col items-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 p-6 text-center text-white transition duration-300"
                                >
                                    @svg('hugeicons-book-open-02', 'mb-4 h-12 w-12')
                                    <h4 class="mb-2 text-xl font-medium">Documentation</h4>
                                    <p class="font-light text-gray-100 group-hover:text-white">
                                        Read guides and our FAQ
                                    </p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
