@section('title',  __('Add Backup Destination'))
<x-app-layout>
    <x-slot name="header">
        {{ __('Add Backup Destination') }}
    </x-slot>
    @livewire('backup-destinations.create-backup-destination-form')
</x-app-layout>
