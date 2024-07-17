<div>
    <div class="my-3 flex justify-end">
        <a href="#" class="text-sm text-red-600 dark:text-red-400 font-medium hover:text-underline ease-in-out" x-data=""
           x-on:click.prevent="$dispatch('open-modal', 'remove-notification-stream-{{ $notificationStream->id }}')">
            @svg('heroicon-o-x-mark', 'h-5 w-5 inline-block -mt-0.5')
            {{ __('Remove Notification Stream') }}
        </a>
    </div>
    <x-modal name="remove-notification-stream-{{ $notificationStream->id }}">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Remove Notification Stream') }}
            </h2>
            <p class="text-gray-800 dark:text-gray-200 my-3">
                {{ __('Are you sure you want to remove the notification stream ":label"?', ['label' => $notificationStream->label]) }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 my-3">
                {{ __('This action cannot be undone.') }}
            </p>
            <div class="flex space-x-5">
                <div class="w-4/6">
                    <x-danger-button type="button" wire:click="delete" class="mt-4" centered action="delete" loadingText="Removing...">
                        {{ __('Confirm Removal') }}
                    </x-danger-button>
                </div>
                <div class="w-2/6 ml-2">
                    <x-secondary-button type="button" class="mt-4" centered x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </x-modal>
</div>
