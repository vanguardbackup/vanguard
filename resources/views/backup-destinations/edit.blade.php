@section('title', __('Update Backup Destination'))
<x-app-layout>
    <x-slot name="header">
        {{ __('Update Backup Destination') }}
    </x-slot>
    @livewire('backup-destinations.update-backup-destination-form', ['backupDestination' => $backupDestination])
    @livewire('backup-destinations.delete-backup-destination-form', ['backupDestination' => $backupDestination])
</x-app-layout>
