@section('title', __('Remote Servers'))
<x-app-layout>
    <x-slot name="header">
        {{ __('Remote Servers') }}
    </x-slot>
    <x-slot name="action">
        @if (!Auth::user()->remoteServers->isEmpty())
            <a href="{{ route('remote-servers.create') }}" wire:navigate>
                <x-primary-button centered>
                    {{ __('Add Remote Server') }}
                </x-primary-button>
            </a>
        @endif
    </x-slot>
    @livewire('remote-servers.index-table')
</x-app-layout>
