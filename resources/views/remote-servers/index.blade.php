@section('title', __('Remote Servers'))
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Remote Servers') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          @if (!Auth::user()->remoteServers->isEmpty())
                <div class="mb-4 sm:mb-6 flex justify-center sm:justify-end">
                    <a href="{{ route('remote-servers.create') }}" wire:navigate class="w-full sm:w-auto">
                        <x-primary-button class="w-full sm:w-auto justify-center">
                            {{ __('Add Remote Server') }}
                        </x-primary-button>
                    </a>
                </div>
            @endif
            @livewire('remote-servers.index-table')
        </div>
    </div>
</x-app-layout>
