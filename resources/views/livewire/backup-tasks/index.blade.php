<div>
    @section('title', __('Backup Tasks'))
    <x-slot name="header">
        {{ __('Backup Tasks') }}
    </x-slot>
    <x-slot name="action">
        @if (!Auth::user()->backupTasks->isEmpty())
         <div class="space-x-2 flex">
             <x-secondary-button x-data="" x-on:click="$dispatch('open-modal', 'copy-backup-task')">
                 @svg('heroicon-o-document-duplicate', 'h-5 w-5')
             </x-secondary-button>
             <a href="{{ route('backup-tasks.create') }}" wire:navigate>
                 <x-primary-button centered>
                     {{ __('Add Backup Task') }}
                 </x-primary-button>
             </a>
         </div>
        @endif
    </x-slot>
    <div>
        @livewire('backup-tasks.tables.index-table')
        @if (!Auth::user()->backupTasks->isEmpty())
            <div>
                @livewire('backup-tasks.tables.backup-task-history-table')
                <div class="mt-4 flex justify-center sm:justify-start">
                    @livewire('backup-tasks.buttons.clear-log-button')
                </div>
            </div>
        @endif
    </div>
    @livewire('backup-tasks.modals.copy-task-modal')
</div>
