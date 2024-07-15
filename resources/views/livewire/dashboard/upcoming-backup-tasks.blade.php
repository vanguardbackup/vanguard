<div>
    @if ($scheduledBackupTasks->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('heroicon-o-calendar', 'h-16 w-16 text-primary-900 dark:text-white inline')
            </x-slot>
            <x-slot name="title">
                {{ __('No Upcoming Backup Tasks') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Uh Oh! There are no backup tasks scheduled.') }}
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('backup-tasks.create') }}" wire:navigate>
                    <x-primary-button type="button" class="mt-4">
                        {{ __('Add Backup Task') }}
                    </x-primary-button>
                </a>
            </x-slot>
        </x-no-content>
    @else
        <x-table.table-wrapper
            title="{{ __('Upcoming Backup Tasks') }}"
            description="{{ __('Scheduled backup tasks that are set to run soon.') }}">
            <x-slot name="icon">
                <x-heroicon-o-calendar class="h-6 w-6 text-primary-600 dark:text-primary-400" />
            </x-slot>
            <x-table.table-header>
                <div class="col-span-3">{{ __('Task Label') }}</div>
                <div class="col-span-3">{{ __('Remote Server') }}</div>
                <div class="col-span-3">{{ __('Task Type') }}</div>
                <div class="col-span-3">{{ __('Scheduled for') }}</div>
            </x-table.table-header>
            <x-table.table-body>
                @foreach ($scheduledBackupTasks as $scheduledBackupTask)
                    <x-table.table-row>
                        <div class="col-span-12 sm:col-span-3 flex flex-col sm:flex-row sm:items-center">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $scheduledBackupTask->task->label }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 sm:hidden">
                                {{ $scheduledBackupTask->due_to_run }}
                            </p>
                        </div>

                        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0">
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $scheduledBackupTask->task->remoteServer->label }}</p>
                        </div>

                        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $scheduledBackupTask->type === 'Files' ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100' : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-800 dark:text-cyan-100' }}">
                                @svg($scheduledBackupTask->type === 'Files' ? 'heroicon-o-document-duplicate' : 'heroicon-o-circle-stack', 'h-4 w-4 mr-1')
                                {{ ucfirst($scheduledBackupTask->type) }}
                            </span>
                        </div>

                        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0 hidden sm:block">
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
