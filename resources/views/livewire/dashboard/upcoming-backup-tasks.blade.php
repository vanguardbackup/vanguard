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
        <x-table.wrapper title="{{ __('Upcoming Backup Tasks') }}" class="grid-cols-8">
            <x-slot name="icon">
                @svg('heroicon-o-calendar', 'h-6 w-6 text-gray-800 dark:text-gray-200 mr-1.5 inline')
            </x-slot>
            <x-slot name="description">
                {{ __('Scheduled backup tasks that are set to run soon.') }}
            </x-slot>
            <x-slot name="header">
                <x-table.header-item class="col-span-2">
                    {{ __('Task Label') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Remote Server') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Task Type') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Scheduled for') }}
                </x-table.header-item>
            </x-slot>
            <x-slot name="body">
                @foreach ($scheduledBackupTasks as $scheduledBackupTask)
                    <x-table.body-item class="col-span-2 hidden md:block">
                        {{ $scheduledBackupTask->task->label }}
                    </x-table.body-item>
                    <x-table.body-item class="col-span-2 hidden md:block">
                        {{ $scheduledBackupTask->task->remoteServer->label }}
                    </x-table.body-item>
                    <x-table.body-item class="col-span-2 hidden md:block">
                        {{ ucfirst($scheduledBackupTask->type) }}
                    </x-table.body-item>
                    <x-table.body-item class="col-span-2">
                        {{ $scheduledBackupTask->due_to_run }}
                    </x-table.body-item>
                @endforeach
            </x-slot>
        </x-table.wrapper>
    @endif
</div>
