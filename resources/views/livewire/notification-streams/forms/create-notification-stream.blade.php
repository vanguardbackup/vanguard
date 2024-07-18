<div>
    @section('title', __('Create Notification Stream'))
    <x-slot name="header">
        {{ __('Create Notification Stream') }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-notification-stream-form :form="$form" submitLabel="{{ __('Save') }}" />
        </div>
    </div>
</div>
