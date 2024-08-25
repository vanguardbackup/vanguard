<div>
    <div class="mt-4">
        @if ($backupTasks->isEmpty())
            <x-no-content withBackground>
                <x-slot name="icon">
                    @svg('hugeicons-archive-02', 'h-16 w-16 text-primary-900 dark:text-white inline')
                </x-slot>
                <x-slot name="title">
                    {{ __("You don't have any backup tasks!") }}
                </x-slot>
                <x-slot name="description">
                    {{ __('You can configure your first backup task by clicking the button below.') }}
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
                title="{{ __('Backup Tasks') }}"
                description="{{ __('An overview of all configured backup tasks along with their current statuses.') }}">
                <x-slot name="icon">
                    <x-hugeicons-archive-02 class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                </x-slot>
                <x-table.table-header>
                    <div class="col-span-12 md:col-span-3">{{ __('Task') }}</div>
                    <div class="col-span-12 md:col-span-3">{{ __('Server & Destination') }}</div>
                    <div class="col-span-12 md:col-span-4">{{ __('Status & Schedule') }}</div>
                    <div class="col-span-12 md:col-span-2">{{ __('Actions') }}</div>
                </x-table.table-header>
                <x-table.table-body>
                    @foreach ($backupTasks as $backupTask)
                        <livewire:backup-tasks.tables.index-item
                            :backupTask="$backupTask"
                            :key="'index-item-' . $backupTask->id"
                        />
                    @endforeach
                </x-table.table-body>
            </x-table.table-wrapper>
            <div class="mt-4 flex justify-end">
                {{ $backupTasks->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', function () {
                Livewire.on('taskUpdated', taskId => {
                    Alpine.store('tagsTooltip').refresh(taskId);
                });
            });
        </script>
    @endpush
</div>
