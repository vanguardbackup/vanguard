@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=Poppins:400,500,600&display=swap" rel="stylesheet" />

        <!-- Icon -->
        @if (config('app.env') === 'local')
            <link rel="icon" href="{{ asset('local_favicon.ico') }}" type="image/x-icon"/>
            <link rel="shortcut icon" href="{{ asset('local_favicon.png') }}" type="image/x-icon"/>
        @else
            <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
            <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/x-icon"/>
        @endif

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#E1E1E1] dark:bg-gray-900">
            <div class="w-full sm:max-w-lg bg-white dark:bg-gray-800 overflow-hidden sm:rounded-[1.15rem] h-auto p-3 shadow-sm">
                <div class="flex justify-evenly">
                    <div class="max-w-lg w-full p-10">
                        <div class="text-center">
                            <a href="/" wire:navigate>
                                <x-application-logo class="w-32 h-32 fill-current text-primary-900 dark:text-white inline" />
                            </a>
                            @isset($title)
                            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 -mt-6">
                                {{ $title }}
                            </h2>
                            @endisset
                            @isset($description)
                            <p class="text-base text-gray-600 font-medium my-5">
                                {{ $description }}
                            </p>
                            @endisset
                        </div>
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
