<div>
    @section('title', __('Backup Tasks'))
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Backup Tasks') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (!Auth::user()->backupTasks->isEmpty())
                <div class="flex justify-end">
                    <a href="{{ route('backup-tasks.create') }}" wire:navigate>
                        <x-primary-button>
                            {{ __('Add Backup Task') }}
                        </x-primary-button>
                    </a>
                </div>
            @endif
            @livewire('backup-tasks.tables.index-table')

            @if (!Auth::user()->backupTasks->isEmpty())
                <div class="mt-6">
                    @livewire('backup-tasks.tables.backup-task-history-table')
                    @livewire('backup-tasks.buttons.clear-log-button')
                </div>
            @endif
        </div>
    </div>
</div>
