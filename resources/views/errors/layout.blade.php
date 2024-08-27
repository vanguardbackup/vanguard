<!DOCTYPE html>
<html lang="en" class="h-full">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>@yield('title') - Vanguard</title>
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        />
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                        },
                        colors: {
                            primary: '#030712',
                            secondary: '#1f2937',
                        },
                        animation: {
                            'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        },
                    },
                },
            };
        </script>
        <style>
            .backup-pattern {
                background-color: #030712;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Cg fill='none' stroke='%23111827' stroke-width='1'%3E%3C!-- Hard Drive --%3E%3Cpath d='M5 5h30v15H5z M7 15h26v4H7z M30 8a2 2 0 1 1 0 4'/%3E%3C!-- Floppy Disk --%3E%3Cpath d='M45 5h30v30H45z M48 7h24v11H48z M52 24h16v8H52z M68 10h4v4h-4z'/%3E%3C!-- Server --%3E%3Cpath d='M85 5h30v30H85z M87 9h26v5H87z M87 16h26v5H87z M87 23h26v5H87z M89 11h2v2h-2z M89 18h2v2h-2z M89 25h2v2h-2z'/%3E%3C!-- Database --%3E%3Cpath d='M5 45q15 -7 30 0 q-15 7 -30 0z M5 45v11q15 7 30 0v-11 M5 56v11q15 7 30 0v-11'/%3E%3C!-- Cloud --%3E%3Cpath d='M45 55a11 11 0 0 1 22 0a9 9 0 0 1 8 15h-30a9 9 0 0 1 0 -15'/%3E%3C!-- Network --%3E%3Cpath d='M95 45v30 M85 60h20 M90 50a5 5 0 1 1 0 1 M90 65a5 5 0 1 1 0 1 M105 60a5 5 0 1 1 0 1'/%3E%3C!-- SSD --%3E%3Cpath d='M5 85h30v30H5z M7 87h26v26H7z M11 91h18v18H11z M15 95h10v10H15z'/%3E%3C!-- Tape --%3E%3Cpath d='M45 85h30v30H45z M50 90a11 11 0 1 1 0 20 M65 90a11 11 0 1 1 0 20 M50 100h20'/%3E%3C!-- RAID --%3E%3Cpath d='M85 85h30v30H85z M87 87h7v26h-7z M96 87h7v26h-7z M105 87h7v26h-7z M89 91h3v3h-3z M98 91h3v3h-3z M107 91h3v3h-3z'/%3E%3C/g%3E%3C/svg%3E");
            }
            [x-cloak] {
                display: none !important;
            }
            @keyframes float {
                0% {
                    transform: translateY(0px);
                }
                50% {
                    transform: translateY(-10px);
                }
                100% {
                    transform: translateY(0px);
                }
            }
            .float-animation {
                animation: float 6s ease-in-out infinite;
            }
        </style>
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon" />
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}" />
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}" />
        <link rel="manifest" href="{{ asset('site.webmanifest') }}" />
        <link rel="mask-icon" href="{{ asset('safari-pinned-tab.svg') }}" color="#020617" />
        <meta name="msapplication-TileColor" content="#020617" />
        <meta name="theme-color" content="#020617" />
    </head>
    <body class="bg-primary backup-pattern h-full font-sans text-gray-200 antialiased">
        <div class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0">
            <div
                x-data="{ show: false, copied: false }"
                x-init="setTimeout(() => (show = true), 100)"
                x-show="show"
                x-transition:enter="transition duration-300 ease-out"
                x-transition:enter-start="scale-95 transform opacity-0"
                x-transition:enter-end="scale-100 transform opacity-100"
                x-cloak
                class="bg-secondary mt-6 w-full overflow-hidden border border-gray-700 px-6 py-8 shadow-2xl sm:max-w-md sm:rounded-lg"
            >
                <div class="text-center">
                    <h1 class="float-animation mb-2 text-6xl font-bold text-white">
                        @yield('code')
                    </h1>
                    <h2 class="mb-4 text-xl font-semibold text-gray-300">
                        @yield('title')
                    </h2>
                    <p class="mb-6 text-base text-gray-400">
                        @yield('message')
                    </p>
                    @hasSection('additional')
                        <p class="mt-2 text-sm text-gray-500">
                            @yield('additional')
                        </p>
                    @endif
                </div>

                @hasSection('linkURL')
                    @hasSection('linkText')
                        <div class="mt-8">
                            <a
                                href="@yield('linkURL')"
                                class="bg-primary flex w-full transform items-center justify-center rounded-md border border-gray-800 px-4 py-2 text-base font-medium text-white shadow-sm transition-all duration-300 ease-in-out hover:scale-105 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2"
                            >
                                @yield('linkText')
                            </a>
                        </div>
                    @endif
                @endif

                <div class="mt-6 text-center">
                    <button
                        @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)"
                        class="text-sm text-gray-400 transition-colors duration-300 hover:text-white focus:underline focus:outline-none"
                    >
                        <span x-show="!copied">Copy page URL</span>
                        <span x-show="copied" x-cloak>URL copied!</span>
                    </button>
                </div>
            </div>
            <div class="mt-8 text-center">
                <a
                    href="/"
                    class="group text-sm font-medium text-gray-400 transition-colors duration-300 hover:text-white"
                >
                    Return to homepage
                    <span
                        class="inline-block transition-transform group-hover:translate-x-1 motion-reduce:transform-none"
                    >
                        →
                    </span>
                </a>
            </div>
        </div>
    </body>
</html>
