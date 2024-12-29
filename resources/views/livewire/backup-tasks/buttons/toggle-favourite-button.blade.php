<div>
    @if ($backupTask->isFavourited())
        <x-primary-button
            wire:click="toggleFavouriteState"
            type="button"
            class="bg-amber-50 !p-2"
            title="{{ __('Click to unpin this task') }}"
        >
            <span class="sr-only">{{ __('Task Unpinned') }}</span>
            @svg('hugeicons-pin-off', 'h-4 w-4')
        </x-primary-button>
    @else
        <x-secondary-button
            wire:click="toggleFavouriteState"
            type="button"
            class="!p-2"
            title="{{ __('Click to pin this task') }}"
        >
            <span class="sr-only">{{ __('Task Pinned') }}</span>
            @svg('hugeicons-pin', 'h-4 w-4')
        </x-secondary-button>
    @endif
</div>
