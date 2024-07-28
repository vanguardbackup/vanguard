<div>
    @section('title', __('Notification Streams'))
    <x-slot name="header">
        {{ __('Notification Streams') }}
    </x-slot>
    <x-slot name="action">
       <a href="{{ route('notification-streams.create') }}" wire:navigate>
           <x-primary-button centered>
               {{ __('Create Notification Stream') }}
           </x-primary-button>
       </a>
    </x-slot>
    @livewire('notification-streams.tables.index-table')
</div>
