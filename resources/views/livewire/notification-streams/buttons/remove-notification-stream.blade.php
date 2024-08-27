<div>
    <div class="my-3 flex justify-end">
        <a
            href="#"
            class="hover:text-underline text-sm font-medium text-red-600 ease-in-out dark:text-red-400"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'remove-notification-stream-{{ $notificationStream->id }}')"
        >
            @svg('hugeicons-delete-02', '-mt-0.5 inline-block h-5 w-5')
            {{ __('Remove Notification Stream') }}
        </a>
    </div>
    <x-modal name="remove-notification-stream-{{ $notificationStream->id }}">
        <x-slot name="title">
            {{ __('Remove Notification Stream') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Please read this carefully before confirming this action.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-delete-02</x-slot>
        <div>
            <p class="mb-3">
                {{ __('Are you sure you want to remove the notification stream ":label"?', ['label' => $notificationStream->label]) }}
            </p>
            <p class="my-3">
                {{ __('This action cannot be undone.') }}
            </p>
            <div class="flex space-x-5">
                <div class="w-4/6">
                    <x-danger-button
                        type="button"
                        wire:click="delete"
                        class="mt-4"
                        centered
                        action="delete"
                        loadingText="Removing..."
                    >
                        {{ __('Confirm Removal') }}
                    </x-danger-button>
                </div>
                <div class="ml-2 w-2/6">
                    <x-secondary-button type="button" class="mt-4" centered x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </x-modal>
</div>
