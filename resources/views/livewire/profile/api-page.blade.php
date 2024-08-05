<div>
    @section('title', __('Manage API Tokens'))
    <x-slot name="header">
        {{ __('Manage API Tokens') }}
    </x-slot>
    <x-slot name="action">
        <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-api-token')">
          {{ __('Create Token') }}
        </x-primary-button>
    </x-slot>
    @livewire('profile.api-token-manager')
</div>
