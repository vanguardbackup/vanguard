<div>
    <x-table.table-row>
        <div class="col-span-12 sm:col-span-3 flex flex-col sm:flex-row sm:items-center">
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $backupDestination->label }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 sm:hidden">
                {{ $backupDestination->type() }}
            </p>
        </div>

        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0 hidden sm:block">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
            {{ $backupDestination->type() }}
        </span>
        </div>

        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0">
            @if ($backupDestination->isReachable())
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                <div class="h-2 w-2 rounded-full bg-green-500 mr-1.5"></div>
                {{ __('Reachable') }}
            </span>
            @elseif ($backupDestination->isUnreachable())
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                <div class="h-2 w-2 rounded-full bg-red-500 mr-1.5"></div>
                {{ __('Unreachable') }}
            </span>
            @elseif ($backupDestination->isChecking())
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                <div class="h-2 w-2 rounded-full bg-purple-500 mr-1.5"></div>
                {{ __('Checking') }}
            </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                <div class="h-2 w-2 rounded-full bg-gray-500 mr-1.5"></div>
                {{ __('Unknown') }}
            </span>
            @endif
        </div>

        <div class="col-span-12 sm:col-span-3 mt-4 sm:mt-0 flex justify-start sm:justify-center space-x-2">
            @if ($backupDestination->type !== \App\Models\BackupDestination::TYPE_LOCAL)
                @livewire('backup-destinations.check-connection-button', ['backupDestination' => $backupDestination], key($backupDestination->id))
            @endif
            <a href="{{ route('backup-destinations.edit', $backupDestination) }}" wire:navigate>
                <x-secondary-button iconOnly>
                    <span class="sr-only">{{ __('Update Backup Destination') }}</span>
                    <x-hugeicons-task-edit-01 class="w-4 h-4"/>
                </x-secondary-button>
            </a>
        </div>
    </x-table.table-row>
</div>
