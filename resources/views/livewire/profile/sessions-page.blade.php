<div>
    @section('title', __('Active Sessions'))
    <x-slot name="header">
        {{ __(' Active Sessions') }}
    </x-slot>
    @livewire('profile.session-manager')
</div>
