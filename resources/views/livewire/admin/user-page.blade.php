<div>
    @section('title', __('Users'))
    <x-slot name="header">
        {{ __('Users') }}
    </x-slot>

    @livewire('admin.user.user-table')
</div>
