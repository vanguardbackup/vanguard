<div>
    @section('title', __('Update Notification Stream'))
    <x-slot name="header">
        {{ __('Update Notification Stream') }}
    </x-slot>
    <x-notification-stream-form :form="$form" submitLabel="{{ __('Save changes') }}" />
    @livewire('notification-streams.buttons.remove-notification-stream', ['notificationStream' => $notificationStream])
</div>
