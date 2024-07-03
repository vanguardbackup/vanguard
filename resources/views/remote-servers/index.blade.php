@section('title', __('Remote Servers'))
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Remote Servers') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          @if (!Auth::user()->remoteServers->isEmpty())
            <div class="flex justify-end">
                <a href="{{ route('remote-servers.create') }}" wire:navigate>
                    <x-primary-button>
                        {{ __('Add Remote Server') }}
                    </x-primary-button>
                </a>
            </div>
            @endif
            @livewire('remote-servers.index-table')
        </div>
    </div>
</x-app-layout>
