<x-app-layout>
    @isset($header)
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $header }}
            </h2>
        </x-slot>
    @endisset

    {{ $slot }}
</x-app-layout>
