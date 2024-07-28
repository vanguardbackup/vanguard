<div>
    @if ($backupTask->isPaused())
        <x-danger-button wire:click="togglePauseState" type="button" class="!p-2" title="{{ __('Click to enable this task') }}">
            <span class="sr-only">{{ __('Task Paused') }}</span>
            @svg('heroicon-o-hand-raised', 'w-4 h-4')
        </x-danger-button>
    @elseif ($backupTask->isRunning())
        <x-secondary-button type="button" class="!p-2 bg-gray-50 cursor-not-allowed" disabled title="{{ __('Pause this task') }}">
            <span class="sr-only">{{ __('Task Not Paused') }}</span>
            @svg('heroicon-o-hand-raised', 'w-4 h-4')
        </x-secondary-button>
    @else
        <x-secondary-button wire:click="togglePauseState" type="button" class="!p-2" title="{{ __('Click to disable this task') }}">
            <span class="sr-only">{{ __('Task Not Paused') }}</span>
            @svg('heroicon-o-hand-raised', 'w-4 h-4')
        </x-secondary-button>
    @endif
</div>
