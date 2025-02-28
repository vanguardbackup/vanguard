<div wire:key="{{ $tableKey }}">
    @if (count($backupTaskLogs) !== 0)
        <div>
            <x-table.table-wrapper
                title="{{ __('Previously Executed Backup Tasks') }}"
                description="{{ __('View your log of previously executed backup tasks.') }}"
            >
                <x-slot name="icon">
                    <x-hugeicons-work-history class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                </x-slot>
                <x-table.table-header>
                    <div class="col-span-2">{{ __('Label') }}</div>
                    <div class="col-span-2">{{ __('Backup Type') }}</div>
                    <div class="col-span-2">
                        {{ __('Backup Destination') }}
                    </div>
                    <div class="col-span-2">{{ __('Result') }}</div>
                    <div class="col-span-2">{{ __('Date') }}</div>
                    <div class="col-span-2">{{ __('Actions') }}</div>
                </x-table.table-header>
                <x-table.table-body>
                    @foreach ($backupTaskLogs as $backupTaskLog)
                        @livewire('backup-tasks.tables.backup-task-history-item', ['backupTaskLog' => $backupTaskLog], key('history-item-' . $backupTaskLog->id))
                    @endforeach
                </x-table.table-body>
            </x-table.table-wrapper>

            <div class="mt-4 flex justify-end">
                {{ $backupTaskLogs->links() }}
            </div>
        </div>
    @endif
</div>
