<x-account-wrapper pageTitle="{{ __('Manage Tags') }}">
    <x-slot name="action">
        @if (!Auth::user()->tags->isEmpty())
            <a href="{{ route('tags.create') }}" wire:navigate class="w-full sm:w-auto">
                <x-primary-button class="w-full sm:w-auto justify-center">
                    {{ __('Create Tag') }}
                </x-primary-button>
            </a>
        @endif
    </x-slot>
    @livewire('tags.index-table')
</x-account-wrapper>
