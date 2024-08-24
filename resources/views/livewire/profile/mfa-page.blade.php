<div>
    @section('title', __('Two-Factor Authentication'))
    <x-slot name="header">
        {{ __('Two-Factor Authentication') }}
    </x-slot>
    @livewire('profile.multi-factor-authentication-manager')
</div>
