@section('title', __('Update Backup Destination'))
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Update Backup Destination') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @livewire('backup-destinations.update-backup-destination-form', ['backupDestination' => $backupDestination])
            @livewire('backup-destinations.delete-backup-destination-form', ['backupDestination' => $backupDestination])
        </div>
    </div>
</x-app-layout>
