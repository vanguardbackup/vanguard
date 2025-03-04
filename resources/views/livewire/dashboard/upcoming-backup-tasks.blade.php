<div>
    @if ($scheduledBackupTasks->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('hugeicons-calendar-03', 'inline h-16 w-16 text-primary-900 dark:text-white')
            </x-slot>
            <x-slot name="title">
                {{ __('No Upcoming Backups') }}
            </x-slot>
            <x-slot name="description">
                {{ __('You don\'t have any backups scheduled yet.') }}
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('backup-tasks.create') }}" wire:navigate>
                    <x-primary-button type="button" class="mt-4">
                        {{ __('Schedule a Backup') }}
                    </x-primary-button>
                </a>
            </x-slot>
        </x-no-content>
    @else
        <x-table.table-wrapper
            title="{{ __('Coming Up Next') }}"
            description="{{ __('Your upcoming scheduled backups.') }}"
        >
            <x-slot name="icon">
                <x-hugeicons-calendar-03 class="h-6 w-6 text-primary-600 dark:text-primary-400" />
            </x-slot>
            <x-table.table-header>
                <div class="col-span-3">{{ __('Backup Name') }}</div>
                <div class="col-span-3">{{ __('Server') }}</div>
                <div class="col-span-3">{{ __('Type') }}</div>
                <div class="col-span-3">{{ __('When') }}</div>
            </x-table.table-header>
            <x-table.table-body>
                @foreach ($scheduledBackupTasks as $scheduledBackupTask)
                    <x-table.table-row>
                        <div class="col-span-12 flex flex-col sm:col-span-3 sm:flex-row sm:items-center">
                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $scheduledBackupTask->task->label }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 sm:hidden dark:text-gray-400">
                                {{ $scheduledBackupTask->due_to_run }}
                            </p>
                        </div>

                        <div class="col-span-12 mt-2 sm:col-span-3 sm:mt-0">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $scheduledBackupTask->task->remoteServer->label }}
                            </p>
                        </div>

                        <div class="col-span-12 mt-2 sm:col-span-3 sm:mt-0">
                            <span
                                class="{{ $scheduledBackupTask->type === __('Files') ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100' : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-800 dark:text-cyan-100' }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            >
                                @svg($scheduledBackupTask->type === __('Files') ? 'hugeicons-file-01' : 'hugeicons-database', ['class' => 'mr-1 h-4 w-4'])
                                {{ ucfirst($scheduledBackupTask->type) }}
                            </span>
                        </div>

                        <div class="col-span-12 mt-2 hidden sm:col-span-3 sm:mt-0 sm:block">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $scheduledBackupTask->due_to_run }}
                            </p>
                        </div>
                    </x-table.table-row>
                @endforeach
            </x-table.table-body>
        </x-table.table-wrapper>
    @endif
</div>
