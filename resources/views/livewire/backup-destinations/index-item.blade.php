<div class="grid gap-0 text-center grid-cols-8">
    <x-table.body-item class="col-span-2 hidden md:block">
        {{ $backupDestination->label }}
    </x-table.body-item>
    <x-table.body-item class="col-span-2 hidden md:block">
        {{ $backupDestination->type() }}
    </x-table.body-item>
    <x-table.body-item class="col-span-2 hidden md:block">
        @if ($backupDestination->isReachable())
            <div class="h-3 w-3 rounded-full inline-flex mr-1 bg-green-600"></div>
            {{ __('Reachable') }}
        @elseif ($backupDestination->isUnreachable())
            <div class="h-3 w-3 rounded-full inline-flex mr-1 bg-red-700"></div>
            {{ __('Unreachable') }}
        @elseif ($backupDestination->isChecking())
            <div class="h-3 w-3 rounded-full inline-flex mr-1 bg-purple-600"></div>
            {{ __('Checking') }}
        @else
            <div class="h-3 w-3 rounded-full inline-flex mr-1 bg-gray-400"></div>
            {{ __('Unknown') }}
        @endif
    </x-table.body-item>
    <x-table.body-item class="col-span-2">
        <div class="flex justify-center space-x-2">
            @livewire('backup-destinations.check-connection-button', ['backupDestination' => $backupDestination],
            key($backupDestination->id))
            <a href="{{ route('backup-destinations.edit', $backupDestination) }}" wire:navigate>
                <x-secondary-button iconOnly>
                    <span class="sr-only">{{ __('Update Backup Destination') }}</span>
                    <x-heroicon-o-pencil-square class="w-4 h-4"/>
                </x-secondary-button>
            </a>
        </div>
    </x-table.body-item>
</div>
