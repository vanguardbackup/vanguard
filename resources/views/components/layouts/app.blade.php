<x-app-layout>
    @isset($header)
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endisset
    @isset($action)
        <x-slot name="action">
            {{ $action }}
        </x-slot>
    @endisset
    {{ $slot }}
</x-app-layout>
