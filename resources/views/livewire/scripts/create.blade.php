<div>
    @section('title', __('Create Script'))
    <x-slot name="header">
        {{ __('Create Script') }}
    </x-slot>
    @livewire('scripts.forms.create-form')
</div>
