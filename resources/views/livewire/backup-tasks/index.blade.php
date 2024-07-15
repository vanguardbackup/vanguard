<div>
    @section('title', __('Backup Tasks'))
    <x-slot name="header">
        {{ __('Backup Tasks') }}
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (!Auth::user()->backupTasks->isEmpty())
                <div class="mb-4 sm:mb-6 flex justify-center sm:justify-end">
                    <a href="{{ route('backup-tasks.create') }}" wire:navigate class="w-full sm:w-auto">
                        <x-primary-button class="w-full sm:w-auto justify-center">
                            {{ __('Add Backup Task') }}
                        </x-primary-button>
                    </a>
                </div>
            @endif

            @livewire('backup-tasks.tables.index-table')

            @if (!Auth::user()->backupTasks->isEmpty())
                <div class="mt-4 sm:mt-6">
                    @livewire('backup-tasks.tables.backup-task-history-table')
                    <div class="mt-4 flex justify-center sm:justify-start">
                        @livewire('backup-tasks.buttons.clear-log-button')
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
