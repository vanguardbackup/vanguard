<div>
    @if ($backupTask->isPaused())
        <x-danger-button
            wire:click="togglePauseState"
            type="button"
            class="!p-2"
            title="{{ __('Click to enable this task') }}"
        >
            <span class="sr-only">{{ __('Task Paused') }}</span>
            @svg('hugeicons-four-finger-03', 'h-4 w-4')
        </x-danger-button>
    @elseif ($backupTask->isRunning())
        <x-secondary-button
            type="button"
            class="cursor-not-allowed bg-gray-50 !p-2"
            disabled
            title="{{ __('Pause this task') }}"
        >
            <span class="sr-only">{{ __('Task Not Paused') }}</span>
            @svg('hugeicons-four-finger-03', 'h-4 w-4')
        </x-secondary-button>
    @else
        <x-secondary-button
            wire:click="togglePauseState"
            type="button"
            class="!p-2"
            title="{{ __('Click to disable this task') }}"
        >
            <span class="sr-only">{{ __('Task Not Paused') }}</span>
            @svg('hugeicons-four-finger-03', 'h-4 w-4')
        </x-secondary-button>
    @endif
</div>
