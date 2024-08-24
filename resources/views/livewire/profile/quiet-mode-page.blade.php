<div>
    @section('title', __('Quiet Mode'))
    <x-slot name="header">
        {{ __('Quiet Mode') }}
    </x-slot>
    @livewire('profile.quiet-mode-manager')
</div>
