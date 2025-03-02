<div>
    @section('title', __('Scripts'))
    <x-slot name="header">
        {{ __('Scripts') }}
    </x-slot>
    <x-slot name="action">
        <a href="{{ route('scripts.create') }}" wire:navigate>
            <x-primary-button centered>
                {{ __('Create Script') }}
            </x-primary-button>
        </a>
    </x-slot>
    @livewire('scripts.tables.index-table')
</div>
