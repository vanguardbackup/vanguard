@section('title', __('Backup Destinations'))
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Backup Destinations') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (!Auth::user()->backupDestinations->isEmpty())
                <div class="mb-4 sm:mb-6 flex justify-center sm:justify-end">
                    <a href="{{ route('backup-destinations.create') }}" wire:navigate class="w-full sm:w-auto">
                        <x-primary-button class="w-full sm:w-auto justify-center">
                            {{ __('Add Backup Destination') }}
                        </x-primary-button>
                    </a>
                </div>
            @endif
            @livewire('backup-destinations.index-table')
        </div>
    </div>
</x-app-layout>
