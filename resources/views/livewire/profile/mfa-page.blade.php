<div>
    @section('title', __('Multi-Factor Authentication (2FA)'))
    <x-slot name="header">
        {{ __(' Multi-Factor Authentication (2FA)') }}
    </x-slot>
    @livewire('profile.multi-factor-authentication-manager')
</div>
