<div>
    @section('title', __('Manage Experiments'))
    <x-slot name="header">
        {{ __('Manage Experiments') }}
    </x-slot>
    @livewire('profile.experiments-manager')
</div>
