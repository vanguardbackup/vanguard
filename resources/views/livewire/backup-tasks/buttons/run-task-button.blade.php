<div>
    @if ($backupTask->isRunning())
        <x-secondary-button type="button" class="!p-2 bg-opacity-50 cursor-not-allowed" disabled title="{{ __('Task is running') }}">
            <span class="sr-only">{{ __('Task Running') }}</span>
            @svg('heroicon-o-stop', 'w-4 h-4')
        </x-secondary-button>
    @elseif ($backupTask->isPaused())
        <x-secondary-button type="button" class="!p-2 cursor-not-allowed bg-opacity-50" disabled title="{{ __('Task is disabled') }}">
            <span class="sr-only">{{ __('Task Disabled') }}</span>
            @svg('heroicon-o-play', 'w-4 h-4')
        </x-secondary-button>
    @elseif ($backupTask->isAnotherTaskRunningOnSameRemoteServer())
        <x-secondary-button type="button" class="!p-2 cursor-not-allowed bg-opacity-50" disabled title="{{ __('Another task is running on the same remote server') }}">
            <span class="sr-only">{{ __('Another task is running on the same remote server') }}</span>
            @svg('heroicon-o-play', 'w-4 h-4')
        </x-secondary-button>
    @elseif ($backupTask->remoteServer->isMarkedForDeletion())
        <x-secondary-button type="button" class="!p-2 cursor-not-allowed bg-opacity-50" disabled title="{{ __('Remote server is marked for deletion') }}">
            <span class="sr-only">{{ __('Remote server is marked for deletion') }}</span>
            @svg('heroicon-o-play', 'w-4 h-4')
        </x-secondary-button>
    @else
        <x-secondary-button wire:click="runTask" type="button" class="!p-2" title="{{ __('Click to run this task') }}">
            <span class="sr-only">Run Task</span>
            @svg('heroicon-o-play', 'w-4 h-4')
        </x-secondary-button>
    @endif
</div>
