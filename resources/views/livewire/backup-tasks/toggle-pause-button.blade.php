<div>
    @if ($backupTask->isPaused())
        <x-danger-button iconOnly wire:click="togglePauseState" type="button" title="{{ __('Click to enable this task') }}">
            <span class="sr-only">Task Paused</span>
            @svg('heroicon-o-hand-raised', 'w-4 h-4')
        </x-danger-button>
        @elseif ($backupTask->isRunning())
        <x-secondary-button iconOnly  type="button" title="{{ __('Pause this task') }}" disabled class="bg-gray-50 cursor-not-allowed">
            <span class="sr-only">Task Not Paused</span>
            @svg('heroicon-o-hand-raised', 'w-4 h-4')
        </x-secondary-button>
    @else
        <x-secondary-button iconOnly wire:click="togglePauseState" type="button" title="{{ __('Click to disable this task') }}">
            <span class="sr-only">Task Not Paused</span>
            @svg('heroicon-o-hand-raised', 'w-4 h-4')
        </x-secondary-button>
    @endif
</div>
