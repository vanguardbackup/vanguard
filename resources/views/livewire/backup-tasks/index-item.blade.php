<div class="grid gap-0 text-center grid-cols-12">
    <x-table.body-item class="col-span-2 hidden md:block text-left">
        <div class="justify-start mx-4 space-x-5 hidden md:flex">
            <div>
                @if ($backupTask->isFilesType())
                    <div id="files-type"
                         class="rounded-full bg-purple-50 dark:bg-purple-950 border border-purple-100 dark:border-purple-900 p-2 overflow-hidden"
                         title="{{ __('Files Task') }}">
                        @svg('heroicon-o-document-duplicate', 'h-5 w-5 text-purple-600 dark:text-purple-400')
                    </div>
                @elseif ($backupTask->isDatabaseType())
                    <div id="database-type"
                         class="rounded-full bg-cyan-50 dark:bg-cyan-950 border border-cyan-100 dark:border-cyan-900 p-2 overflow-hidden"
                         title="{{ __('Database Task') }}">
                        @svg('heroicon-o-circle-stack', 'h-5 w-5 text-cyan-600 dark:text-cyan-400')
                    </div>
                @endif
            </div>
            <div>
                @if ($backupTask->tags()->exists())
                    <span id="tags">
                    @svg('heroicon-o-tag', 'h-5 w-5 text-gray-400 dark:text-gray-600 inline')
                </span>
                @endif
                <span>{{ $backupTask->label }}</span>
            </div>
        </div>
    </x-table.body-item>
    <x-table.body-item class="col-span-2 hidden md:block">
        {{ $backupTask->remoteServer->label }}
    </x-table.body-item>
    <x-table.body-item class="col-span-2 hidden md:block">
        {{ $backupTask->backupDestination->label }} ({{ $backupTask->backupDestination->type() }})
    </x-table.body-item>
    <x-table.body-item class="col-span-1 font-medium">
        @if ($backupTask->isPaused())
            @svg('heroicon-o-exclamation-triangle', 'h-5 w-5 text-red-600 dark:text-red-400 inline mr-2')
            <span class="text-red-600 dark:text-red-400">{{ __('Paused') }}</span>
        @else
            {{ ucfirst($backupTask->status) }}
        @endif
    </x-table.body-item>
    <x-table.body-item class="col-span-2">
        <div>
            <span>
                {{ __('Scheduled') }}:
            </span>
            <span class="text-xs text-gray-600 dark:text-gray-50">
                @if ($backupTask->isPaused())
                    {{ __('— N/A') }}
                    @else
                    @if ($backupTask->usingCustomCronExpression())
                        ({{ $backupTask->custom_cron_expression }})
                    @else
                        {{ ucfirst($backupTask->frequency) }} {{ __('at') }} {{ $backupTask->time_to_run_at }}
                    @endif
                @endif
            </span>
        </div>
        <span>
            {{ __('Last ran') }}:
        </span>
        <span class="text-xs text-gray-600 dark:text-gray-50">
            {{ $backupTask->last_run_at ? $backupTask->last_run_at->timezone(Auth::user()->timezone ?? config('app.timezone'))->format('d F Y H:i') : __('Never') }}
        </span>

    </x-table.body-item>
    <x-table.body-item class="col-span-3">
        <div class="flex justify-start space-x-2">
            <livewire:backup-tasks.run-task-button :$backupTask :key="'run-task-button-' . $backupTask->id"/>
            @if ($backupTaskLog)
                <x-secondary-button x-data=""
                                    x-on:click.prevent="$dispatch('open-modal', 'backup-task-{{ $backupTask->id }}')"
                                    iconOnly title="{{ __('Click to view this log') }}">
                    @svg('heroicon-o-document-text', 'h-4 w-4')
                    <span class="sr-only">{{ __('View Log') }}</span>
                </x-secondary-button>
            @else
                <x-secondary-button class="bg-opacity-50 cursor-not-allowed" disabled iconOnly
                                    title="{{ __('No log available') }}">
                    @svg('heroicon-o-document-text', 'h-4 w-4')
                    <span class="sr-only">{{ __('View Log') }}</span>
                </x-secondary-button>
            @endif
            <livewire:backup-tasks.toggle-pause-button :backupTask="$backupTask"
                                                       :key="'toggle-pause-button-' . $backupTask->id"/>
            <a href="{{ route('backup-tasks.edit', $backupTask) }}" wire:navigate>
                <x-secondary-button iconOnly>
                    <span class="sr-only">{{ __('Update Backup Task') }}</span>
                    <x-heroicon-o-pencil-square class="w-4 h-4"/>
                </x-secondary-button>
            </a>
        </div>
    </x-table.body-item>
    @if ($backupTaskLog)
        <livewire:backup-tasks.log-modal :$backupTask :$backupTaskLog :key="'show-log-modal-' . $backupTask->id"/>
    @endif
</div>

@if ($backupTask->tags()->exists())
    <script>
        document.addEventListener('livewire:init', function () {
            tippy('#tags', {
                content: 'Has tags: {{ $backupTask->listOfAttachedTagLabels() }}',
            });
        });
    </script>
@endif
