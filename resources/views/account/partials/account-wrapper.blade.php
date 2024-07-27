@props(['pageTitle' => __('Account')])
@section('title', $pageTitle)
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>
    <div class="py-6 sm:py-8 lg:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                <nav class="w-full lg:w-64 xl:w-72 shrink-0">
                    <x-account-sidebar/>
                </nav>
                <main class="flex-1 min-w-0">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
