@section('title', __('Add Remote Server'))
<x-app-layout>
    <x-slot name="header">
        {{ __('Add Remote Server') }}
    </x-slot>
    @livewire('remote-servers.create-remote-server-form')
</x-app-layout>
