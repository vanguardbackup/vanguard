<div>
    @section('title', __('Manage Sessions'))
    <x-slot name="header">
        {{ __(' Manage Sessions') }}
    </x-slot>
    @livewire('profile.session-manager')
</div>
