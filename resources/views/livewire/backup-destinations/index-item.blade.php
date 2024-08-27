<div>
    <x-table.table-row>
        <div class="col-span-12 flex flex-col sm:col-span-3 sm:flex-row sm:items-center">
            <p class="font-medium text-gray-900 dark:text-gray-100">
                {{ $backupDestination->label }}
            </p>
            <p class="mt-1 text-xs text-gray-500 sm:hidden dark:text-gray-400">
                {{ $backupDestination->type() }}
            </p>
        </div>

        <div class="col-span-12 mt-2 hidden sm:col-span-3 sm:mt-0 sm:block">
            <span
                class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-800 dark:text-blue-100"
            >
                {{ $backupDestination->type() }}
            </span>
        </div>

        <div class="col-span-12 mt-2 sm:col-span-3 sm:mt-0">
            @if ($backupDestination->isReachable())
                <span
                    class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-800 dark:text-green-100"
                >
                    <div class="mr-1.5 h-2 w-2 rounded-full bg-green-500"></div>
                    {{ __('Reachable') }}
                </span>
            @elseif ($backupDestination->isUnreachable())
                <span
                    class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-800 dark:text-red-100"
                >
                    <div class="mr-1.5 h-2 w-2 rounded-full bg-red-500"></div>
                    {{ __('Unreachable') }}
                </span>
            @elseif ($backupDestination->isChecking())
                <span
                    class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-800 dark:text-purple-100"
                >
                    <div class="mr-1.5 h-2 w-2 rounded-full bg-purple-500"></div>
                    {{ __('Checking') }}
                </span>
            @else
                <span
                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-100"
                >
                    <div class="mr-1.5 h-2 w-2 rounded-full bg-gray-500"></div>
                    {{ __('Unknown') }}
                </span>
            @endif
        </div>

        <div class="col-span-12 mt-4 flex justify-start space-x-2 sm:col-span-3 sm:mt-0 sm:justify-center">
            @if ($backupDestination->type !== \App\Models\BackupDestination::TYPE_LOCAL)
                @livewire('backup-destinations.check-connection-button', ['backupDestination' => $backupDestination], key($backupDestination->id))
            @endif

            <a href="{{ route('backup-destinations.edit', $backupDestination) }}" wire:navigate>
                <x-secondary-button iconOnly>
                    <span class="sr-only">
                        {{ __('Update Backup Destination') }}
                    </span>
                    <x-hugeicons-task-edit-01 class="h-4 w-4" />
                </x-secondary-button>
            </a>
        </div>
    </x-table.table-row>
</div>
