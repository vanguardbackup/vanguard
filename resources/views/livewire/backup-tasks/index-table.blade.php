<div>
    <div class="mt-4">
        @if ($backupTasks->isEmpty())
            <x-no-content withBackground>
                <x-slot name="icon">
                    @svg('heroicon-o-cloud-arrow-up', 'h-16 w-16 text-primary-900 dark:text-white inline')
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
            <x-table.wrapper title="{{ __('Backup Tasks') }}" class="grid-cols-12">
                <x-slot name="header">
                    <x-table.header-item class="col-span-2">
                        {{ __('Label') }}
                    </x-table.header-item>
                    <x-table.header-item class="col-span-2">
                        {{ __('Remote Server') }}
                    </x-table.header-item>
                    <x-table.header-item class="col-span-2">
                        {{ __('Backup Destination') }}
                    </x-table.header-item>
                    <x-table.header-item class="col-span-1">
                        {{ __('Status') }}
                    </x-table.header-item>
                    <x-table.header-item class="col-span-2">
                        {{ __('Time') }}
                    </x-table.header-item>
                    <x-table.header-item class="col-span-3">
                        {{ __('Actions') }}
                    </x-table.header-item>
                </x-slot>
                <x-slot name="advancedBody">
                    @foreach ($backupTasks as $backupTask)
                        <livewire:backup-tasks.index-item :backupTask="$backupTask" :key="'index-item-' . $backupTask->id" />
                    @endforeach
                </x-slot>
            </x-table.wrapper>
            <div class="mt-4 flex justify-end">
                {{ $backupTasks->links() }}
            </div>
        @endif
    </div>
    <script>
        document.addEventListener('livewire:navigated', function () {
            let filesType = @json(__('Files Task'), JSON_THROW_ON_ERROR);
            let databaseType = @json(__('Database Task'), JSON_THROW_ON_ERROR);

            tippy('#files-type', {
                content: filesType,
            });
            tippy('#database-type', {
                content: databaseType
            });
        });
    </script>
</div>
