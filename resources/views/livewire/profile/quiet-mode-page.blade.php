<div>
    @section('title', __('Manage Quiet Mode'))
    <x-slot name="header">
        {{ __(' Manage Quiet Mode') }}
    </x-slot>
    @livewire('profile.quiet-mode-manager')
</div>
