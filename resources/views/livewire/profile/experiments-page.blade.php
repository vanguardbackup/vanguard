<div>
    @section('title', __('Experiments'))
    <x-slot name="header">
        {{ __('Experiments') }}
    </x-slot>
    @livewire('profile.experiments-manager')
</div>
