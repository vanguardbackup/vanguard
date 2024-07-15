@props(['pageTitle' => __('Account')])
@section('title', $pageTitle)
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row xl:mt-8">
            <nav class="relative mb-4 w-full lg:mb-0 lg:w-1/5 xl:w-1/6 lg:pr-6">
                <x-account-sidebar />
            </nav>
            <div class="flex flex-col lg:w-4/5 xl:w-5/6">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-app-layout>
