<div>
    <x-table.table-row>
        <div class="col-span-12 sm:col-span-3 flex flex-col sm:flex-row sm:items-center">
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $remoteServer->label }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 sm:hidden">
                {{ $remoteServer->ip_address }}
            </p>
        </div>

        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0 hidden sm:block">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-normal text-gray-800 dark:text-gray-100">
                {{ $remoteServer->ip_address }}
            </span>
        </div>

        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0">
            @if ($remoteServer->isOnline())
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                    <div class="h-2 w-2 rounded-full bg-green-500 mr-1.5"></div>
                    {{ __('Online') }}
                </span>
            @elseif ($remoteServer->isOffline())
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                    <div class="h-2 w-2 rounded-full bg-red-500 mr-1.5"></div>
                    {{ __('Offline') }}
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                    <div class="h-2 w-2 rounded-full bg-purple-500 mr-1.5"></div>
                    {{ __('Checking') }}
                </span>
            @endif
        </div>

        <div class="col-span-12 sm:col-span-3 mt-4 sm:mt-0 flex justify-start sm:justify-center space-x-2">
            @livewire('remote-servers.check-connection-button', ['remoteServer' => $remoteServer], key($remoteServer->id))
            @if ($remoteServer->backupTasks->count() === 0)
                <x-secondary-button iconOnly disabled class="cursor-not-allowed opacity-50" title="{{ __('No linked backup tasks..') }}">
                    <span class="sr-only">{{ __('View Linked Backup Tasks') }}</span>
                    <x-heroicon-o-list-bullet class="w-4 h-4"/>
                </x-secondary-button>
            @else
                <x-secondary-button iconOnly x-on:click="$dispatch('open-modal', 'view-linked-backup-tasks-{{ $remoteServer->id }}')">
                    <span class="sr-only">{{ __('View Linked Backup Tasks') }}</span>
                    <x-heroicon-o-list-bullet class="w-4 h-4"/>
                </x-secondary-button>
            @endif
            <a href="{{ route('remote-servers.edit', $remoteServer) }}" wire:navigate>
                <x-secondary-button iconOnly>
                    <span class="sr-only">{{ __('Update Remote Server') }}</span>
                    <x-heroicon-o-pencil-square class="w-4 h-4"/>
                </x-secondary-button>
            </a>
        </div>
    </x-table.table-row>

    <!-- Linked Backup Tasks Modal -->
    <x-modal name="view-linked-backup-tasks-{{ $remoteServer->id }}" :key="'linked-tasks-modal-' . $remoteServer->id" focusable>
        <x-slot name="title">
            {{ __('Backup Tasks for :server', ['server' => $remoteServer->label]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('Manage and monitor backup tasks associated with :server.', ['server' => $remoteServer->label]) }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-list-bullet
        </x-slot>
        <div class="space-y-4 max-h-96 overflow-y-auto">
            @forelse ($remoteServer->backupTasks as $backupTask)
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 flex items-center space-x-2">
                            @if ($backupTask->isFilesType())
                                @svg('heroicon-o-document-duplicate', 'h-5 w-5 text-purple-600 dark:text-purple-400')
                            @elseif ($backupTask->isDatabaseType())
                                @svg('heroicon-o-circle-stack', 'h-5 w-5 text-cyan-600 dark:text-cyan-400')
                            @endif
                            <span>{{ $backupTask->label }}</span>
                        </h3>
                        <div>
                            @if ($backupTask->isPaused())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                    @svg('heroicon-o-pause', 'h-3.5 w-3.5 mr-1')
                                    {{ __('Paused') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $backupTask->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' }}">
                                    @if ($backupTask->status === 'ready')
                                        @svg('heroicon-o-check-circle', 'h-3.5 w-3.5 mr-1')
                                    @else
                                        @svg('heroicon-o-arrow-path', 'h-3.5 w-3.5 mr-1 animate-spin')
                                    @endif
                                    {{ $backupTask->status === 'ready' ? __('Ready') : __('Running') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-0">
                        <dl class="sm:divide-y sm:divide-gray-200 dark:sm:divide-gray-700">
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Destination') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                                    {{ $backupTask->backupDestination->label }} ({{ $backupTask->backupDestination->type() }})
                                </dd>
                            </div>
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Schedule') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                                    @if ($backupTask->isPaused())
                                        {{ __('N/A') }}
                                    @else
                                        @if ($backupTask->usingCustomCronExpression())
                                            {{ $backupTask->custom_cron_expression }}
                                        @else
                                            {{ ucfirst(__($backupTask->frequency)) }} {{ __('at') }} {{ $backupTask->runTimeFormatted(Auth::user()) }}
                                        @endif
                                    @endif
                                </dd>
                            </div>
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Last ran') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                                    {{ $backupTask->lastRunFormatted(Auth::user()) ?? __('Never') }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                    {{ __('No backup tasks linked to this server.') }}
                </div>
            @endforelse
        </div>
        <div class="mt-6">
            <x-secondary-button x-on:click="$dispatch('close')" class="w-full justify-center">
                {{ __('Close') }}
            </x-secondary-button>
        </div>
    </x-modal>
</div>
