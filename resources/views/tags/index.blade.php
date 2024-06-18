@section('title', 'Tags')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tags') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (!Auth::user()->tags->isEmpty())
                <div class="flex justify-end">
                    <a href="{{ route('tags.create') }}" wire:navigate>
                        <x-primary-button>
                            {{ __('Create Tag') }}
                        </x-primary-button>
                    </a>
                </div>
            @endif
            @livewire('tags.index-table')
        </div>
    </div>
</x-app-layout>
