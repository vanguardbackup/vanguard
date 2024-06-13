@section('title', 'Update Remote Server')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Update Remote Server') }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @livewire('remote-servers.update-remote-server-form', ['remoteServer' => $remoteServer])
            @livewire('remote-servers.delete-remote-server-form', ['remoteServer' => $remoteServer])
        </div>
    </div>
</x-app-layout>
