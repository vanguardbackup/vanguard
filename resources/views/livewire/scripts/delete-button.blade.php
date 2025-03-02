<div>
    <x-secondary-button iconOnly x-on:click.prevent="$dispatch('open-modal', 'remove-script-{{ $script->id }}')">
        <span class="sr-only">
            {{ __('Remove Script') }}
        </span>
        <x-hugeicons-delete-02 class="h-4 w-4" />
    </x-secondary-button>
    <x-modal name="remove-script-{{ $script->id }}">
        <x-slot name="title">
            {{ __('Confirm Script Removal') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Please read this carefully before confirming this action.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-delete-02</x-slot>
        <div>
            <p class="mb-3">
                {{ __('Are you sure you want to remove the script ":label"?', ['label' => $script->label]) }}
            </p>
            <p class="my-3">
                {{ __('This action cannot be undone, this script will be permanently deleted.') }}
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
                        {{ __('Confirm') }}
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
