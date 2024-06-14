<div>
    @if (count($backupTaskLogs) !== 0)
        <div>
            <x-table.wrapper title="{{ __('Previously Ran Backup Tasks') }}" class="grid-cols-11">
                <x-slot name="header">
                    <x-table.header-item class="col-span-2">
                        {{ __('Label') }}
                    </x-table.header-item>
                    <x-table.header-item class="col-span-2">
                        {{ __('Backup Type') }}
                    </x-table.header-item>
                    <x-table.header-item class="col-span-2">
                        {{ __('Backup Destination') }}
                    </x-table.header-item>
                    <x-table.header-item class="col-span-2">
                        {{ __('Result') }}
                    </x-table.header-item>
                    <x-table.header-item class="col-span-2">
                        {{ __('Date') }}
                    </x-table.header-item>
                    <x-table.header-item class="col-span-1">
                        {{ __('Actions') }}
                    </x-table.header-item>
                </x-slot>
                <x-slot name="advancedBody">
                    @foreach ($backupTaskLogs as $backupTaskLog)
                        <div class="grid gap-0 text-center grid-cols-11" wire:key="{{ $backupTaskLog->id }}}">
                            <x-table.body-item class="col-span-2 hidden md:block">
                                {{ $backupTaskLog->backupTask->label }}
                            </x-table.body-item>
                            <x-table.body-item class="col-span-2 hidden md:block">
                                {{ ucfirst($backupTaskLog->backupTask->type) }}
                            </x-table.body-item>
                            <x-table.body-item class="col-span-2 hidden md:block">
                                {{ $backupTaskLog->backupTask->backupDestination->label }}
                                ({{ $backupTaskLog->backupTask->backupDestination->type() }})
                            </x-table.body-item>
                            <x-table.body-item class="col-span-2 hidden md:block">
                                {{ (bool) $backupTaskLog->successful_at ? __('Finished') : __('Failed') }}
                            </x-table.body-item>
                            <x-table.body-item class="col-span-2 hidden md:block">
                                {{ $backupTaskLog->created_at ? $backupTaskLog->created_at->timezone(Auth::user()->timezone ?? config('app.timezone'))->format('l, d F Y H:i') : __('Never') }}
                            </x-table.body-item>
                            <x-table.body-item class="col-span-1">
                                <div class="flex justify-evenly space-x-2">
                                    <x-secondary-button x-data=""
                                                        x-on:click.prevent="$dispatch('open-modal', 'backup-task-historic-log-{{ $backupTaskLog->id }}')"
                                                        iconOnly title="{{ __('Click to view this log') }}">
                                        @svg('heroicon-o-document-text', 'h-4 w-4')
                                        <span class="sr-only">{{ __('View Log') }}</span>
                                    </x-secondary-button>
                                    <x-secondary-button x-data=""
                                                        x-on:click.prevent="$dispatch('open-modal', 'backup-task-remove-historic-log-{{ $backupTaskLog->id }}')" iconOnly title="{{ __('Remove this log') }}">
                                        @svg('heroicon-o-trash', 'h-4 w-4')
                                        <span class="sr-only">{{ __('Remove Log') }}</span>
                                    </x-secondary-button>
                                </div>
                            </x-table.body-item>
                        </div>
                    @livewire('backup-tasks.delete-backup-task-log-button', ['backupTaskLog' => $backupTaskLog])
                        <x-modal name="backup-task-historic-log-{{ $backupTaskLog->id }}">
                            <div class="p-6 text-center">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ __('Viewing log for finished task: ":label".', ['label' => $backupTaskLog->backupTask->label]) }}
                                </h2>
                                <p class="text-gray-800 dark:text-gray-200 my-3">
                                    {{ __('This log was generated :date.', ['date' => $backupTaskLog->created_at->timezone(Auth::user()->timezone ?? config('app.timezone'))->format('l, d F Y H:i')]) }}
                                </p>
                                <div class="my-5">
                                    <x-textarea id="logOutput" readonly
                                                class="pre text-sm text-gray-800 bg-gray-50 font-mono" rows="16" wrap>
                                        {{ $backupTaskLog->output }}
                                    </x-textarea>
                                </div>
                                <div class="mt-6">
                                    <x-secondary-button x-on:click="$dispatch('close')" centered>
                                        {{ __('Close') }}
                                    </x-secondary-button>
                                </div>
                            </div>
                        </x-modal>
                    @endforeach
                </x-slot>
            </x-table.wrapper>
            <div class="mt-4 flex justify-end">
                {{ $backupTaskLogs->links() }}
            </div>
        </div>
    @endif
</div>
