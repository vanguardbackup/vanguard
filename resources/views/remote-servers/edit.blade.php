@section('title', __('Update Remote Server'))
<x-app-layout>
    <x-slot name="header">
        {{ __('Update Remote Server') }}
    </x-slot>
    @livewire('remote-servers.update-remote-server-form', ['remoteServer' => $remoteServer])
    @livewire('remote-servers.delete-remote-server-form', ['remoteServer' => $remoteServer])
</x-app-layout>
