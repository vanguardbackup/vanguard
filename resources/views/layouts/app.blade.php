<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>@yield('title', '') | {{ config('app.name') }}</title>

        <!-- Basic Metadata -->
        <meta
            name="description"
            content="{{ config('app.name') }} - Open-source backup solution for servers and applications"
        />

        <!-- Open Graph / Discord -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:title" content="{{ config('app.name') }}" />
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
    <body class="font-sans antialiased">
        <livewire:other.new-feature-banner />
        @include('partials.missing-keys-and-passphrase')
        <div class="min-h-screen bg-primary-100 dark:bg-gray-900">
            <livewire:layout.navigation />
            @if (session()->has('flash_message'))
                @php
                    $flashMessage = session('flash_message');
                @endphp

                <x-partials.flash-message
                    :message="$flashMessage['message']"
                    :type="$flashMessage['type']"
                    :dismissible="$flashMessage['dismissible']"
                />
            @endif

            @include('partials.quiet-mode-banner')
            {{ Breadcrumbs::render() }}
            <!-- Page Heading -->
            @if (isset($header))
                <header>
                    <div class="mx-auto">
                        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
                            <div class="flex min-h-[48px] items-center justify-between">
                                <div class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                                    {{ $header }}
                                </div>
                                <div class="flex items-center">
                                    <div class="hidden md:block">
                                        @if (isset($action))
                                            {{ $action }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                @if (isset($outsideContainer))
                    {{ $outsideContainer }}
                @else
                    <div class="mx-auto max-w-6xl px-4 pb-20 sm:px-6 lg:px-8">
                        <div class="block w-full md:hidden">
                            @if (isset($action))
                                {{ $action }}
                            @endif
                        </div>
                        {{ $slot }}
                    </div>
                @endif
            </main>
        </div>
        <x-toaster-hub />
        <footer class="relative bg-gray-50 py-12 text-gray-600 sm:py-16 dark:bg-gray-900 dark:text-gray-300">
            <div
                class="absolute left-0 right-0 top-0 h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent dark:via-gray-700"
            ></div>
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-12">
                    <div class="text-center sm:text-left">
                        <h2 class="mb-3 text-2xl font-bold text-gray-900 dark:text-white">
                            {{ config('app.name') }}
                        </h2>
                        <p class="mb-4 text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ __('Version :version', ['version' => obtain_vanguard_version()]) }}
                        </p>
                        <div class="mb-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                            @if (config('app.env') === 'local')
                                <span
                                    class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700 shadow-sm ring-1 ring-indigo-500/20 dark:bg-indigo-900/40 dark:text-indigo-300 dark:ring-indigo-400/30"
                                >
                                    @svg('hugeicons-laptop', ['class' => 'mr-1.5 h-3.5 w-3.5 text-indigo-500 dark:text-indigo-400'])
                                    {{ __('Local') }}
                                </span>
                            @endif

                            @if (config('app.debug'))
                                <span
                                    class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700 shadow-sm ring-1 ring-rose-500/20 dark:bg-rose-900/40 dark:text-rose-300 dark:ring-rose-400/30"
                                >
                                    @svg('hugeicons-bug-01', ['class' => 'mr-1.5 h-3.5 w-3.5 text-rose-500 dark:text-rose-400'])
                                    {{ __('Debug') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @foreach ([
                            'Account' => [
                                [
                                    'route' => 'profile',
                                    'icon' => 'hugeicons-user',
                                    'label' => __('My Profile')
                                ],
                                [
                                    'route' => 'notification-streams.index',
                                    'icon' => 'hugeicons-notification-02',
                                    'label' => __('Notifications')
                                ],
                                [
                                    'route' => 'statistics',
                                    'icon' => 'hugeicons-analytics-01',
                                    'label' => __('Statistics')
                                ]
                            ],
                            'Resources' => [
                                [
                                    'url' => 'https://docs.vanguardbackup.com',
                                    'icon' => 'hugeicons-book-02',
                                    'label' => __('Documentation')
                                ],
                                [
                                    'url' => 'https://github.com/vanguardbackup/vanguard',
                                    'icon' => 'hugeicons-github',
                                    'label' => __('GitHub')
                                ],
                                [
                                    'route' => 'profile.help',
                                    'icon' => 'hugeicons-mentoring',
                                    'label' => __('Help Center')
                                ]
                            ],
                            'Community' => [
                                [
                                    'url' => 'https://github.com/vanguardbackup/vanguard/discussions',
                                    'icon' => 'hugeicons-chatting-01',
                                    'label' => __('Discussions')
                                ],
                                [
                                    'url' => 'https://github.com/vanguardbackup/vanguard/issues',
                                    'icon' => 'hugeicons-bug-01',
                                    'label' => __('Report an Issue')
                                ],
                                [
                                    'url' => 'https://github.com/vanguardbackup/vanguard/blob/main/CONTRIBUTING.md',
                                    'icon' => 'hugeicons-agreement-01',
                                    'label' => __('Contribute')
                                ]
                            ]
                        ]
                        as $title => $links)
                        <div class="text-center sm:text-left">
                            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ __($title) }}
                            </h2>
                            <ul class="space-y-3">
                                @foreach ($links as $link)
                                    <li>
                                        <a
                                            href="{{ isset($link['route']) ? route($link['route']) : $link['url'] }}"
                                            @if (isset($link['url'])) target="_blank" rel="noopener noreferrer" @endif
                                            class="group flex items-center justify-center text-gray-600 transition duration-150 ease-in-out hover:text-primary-600 sm:justify-start dark:text-gray-300 dark:hover:text-primary-400"
                                        >
                                            @svg($link['icon'], ['class' => 'mr-2 h-5 w-5 text-gray-400 transition-colors duration-150 ease-in-out group-hover:text-primary-500'])
                                            <span class="group-hover:underline">
                                                {{ $link['label'] }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
                <div class="mt-12 border-t border-gray-200 pt-8 text-center text-sm dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">
                        &copy; {{ date('Y') }} {{ config('app.name') }}.
                        {{ __('This software is open source and available under the') }}
                        <a
                            href="https://opensource.org/licenses/agpl-v3"
                            class="text-primary-600 hover:text-primary-800 hover:underline dark:text-primary-400 dark:hover:text-primary-300"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ __('AGPLv3 License') }}
                        </a>
                        .
                    </p>
                </div>
            </div>
        </footer>
    </body>
    @livewire('livewire-ui-spotlight')
</html>
