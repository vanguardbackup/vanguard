<div>
    @section('title', __('Update Notification Stream'))
    <x-slot name="header">
        {{ __('Update Notification Stream') }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-notification-stream-form :form="$form" submitLabel="{{ __('Save changes') }}" />
           @livewire('notification-streams.buttons.remove-notification-stream', ['notificationStream' => $notificationStream])
        </div>
    </div>
</div>
