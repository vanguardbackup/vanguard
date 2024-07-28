@section('title', __('Backup Destinations'))
<x-app-layout>
    <x-slot name="header">
        {{ __('Backup Destinations') }}
    </x-slot>
    <x-slot name="action">
        @if (!Auth::user()->backupDestinations->isEmpty())
            <a href="{{ route('backup-destinations.create') }}" wire:navigate>
                <x-primary-button centered>
                    {{ __('Add Backup Destination') }}
                </x-primary-button>
            </a>
        @endif
    </x-slot>
    @livewire('backup-destinations.index-table')
</x-app-layout>
