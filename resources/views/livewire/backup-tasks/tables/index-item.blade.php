<div class="mb-4 overflow-hidden rounded-none bg-transparent transition-all duration-300">
    <div class="space-y-4 p-3">
        <!-- Responsive View (Collapsible) -->
        <div class="md:hidden">
            <!-- Task Type, Label, and Status - Always visible -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        @if ($backupTask->isFilesType())
                            <div class="group relative" title="{{ __('Files Task') }}">
                                <div
                                    class="absolute inset-0 rotate-3 transform rounded-lg bg-gradient-to-br from-purple-300 to-purple-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105 dark:from-purple-600 dark:to-purple-700"
                                ></div>
                                <div
                                    class="relative rounded-lg bg-white p-2 shadow-sm transition-all duration-300 group-hover:shadow-md dark:bg-gray-800"
                                >
                                    @svg(
                                        'hugeicons-file-01',
                                        'h-5 w-5 text-purple-600
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                dark:text-purple-400'
                                    )
                                </div>
                            </div>
                        @elseif ($backupTask->isDatabaseType())
                            <div class="group relative" title="{{ __('Database Task') }}">
                                <div
                                    class="absolute inset-0 rotate-3 transform rounded-lg bg-gradient-to-br from-cyan-300 to-cyan-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105 dark:from-cyan-600 dark:to-cyan-700"
                                ></div>
                                <div
                                    class="relative rounded-lg bg-white p-2 shadow-sm transition-all duration-300 group-hover:shadow-md dark:bg-gray-800"
                                >
                                    @svg('hugeicons-database', 'h-5 w-5 text-cyan-600 dark:text-cyan-400')
                                </div>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $backupTask->label }}
                        </h3>
                        <div class="flex space-x-2">
                            @if ($backupTask->tags()->exists())
                                <div
                                    x-data
                                    x-on:mouseenter="
                                        $dispatch('show-tags-tooltip', {
                                            id: {{ $backupTask->id }},
                                            tags: '{{ $backupTask->listOfAttachedTagLabels() }}',
                                        })
                                    "
                                >
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        @svg('hugeicons-tags', 'mr-1 h-3 w-3 text-gray-500 dark:text-gray-400')
                                        {{ $backupTask->tags->count() }}
                                    </span>
                                </div>
                            @endif

                            @if ($backupTask->notificationStreams()->exists())
                                <div
                                    x-data
                                    x-on:mouseenter="
                                        $dispatch('show-tags-tooltip', {
                                            id: {{ $backupTask->id }},
                                            tags: '{{ $backupTask->listOfAttachedTagLabels() }}',
                                        })
                                    "
                                >
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        @svg('hugeicons-notification-02', 'mr-1 h-3 w-3 text-gray-500 dark:text-gray-400')
                                        {{ $backupTask->notificationStreams->count() }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="text-sm font-medium">
                    @if ($backupTask->isPaused())
                        <span
                            class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-800 dark:text-red-100"
                        >
                            @svg('hugeicons-pause', 'mr-1 h-3.5 w-3.5')
                            {{ __('Paused') }}
                        </span>
                    @else
                        <span
                            class="{{ $backupTask->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                        >
                            @if ($backupTask->status === 'ready')
                                @svg('hugeicons-checkmark-circle-02', 'mr-1 h-3.5 w-3.5')
                            @else
                                @svg('hugeicons-refresh', 'mr-1 h-3.5 w-3.5 animate-spin')
                            @endif
                            {{ $backupTask->status === 'ready' ? __('Ready') : __('Running') }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Server, Destination, and Schedule - Collapsible Responsively -->
            <div x-data="{ open: false }" class="my-3">
                <button
                    @click="open = !open"
                    class="text-sm text-gray-600 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
                >
                    {{ __('Details') }}
                    <span x-show="!open">
                        @svg('hugeicons-arrow-down-01', ['class' => 'inline h-4 w-4 text-gray-900 dark:text-gray-50'])
                    </span>
                    <span x-show="open">
                        @svg('hugeicons-arrow-up-01', ['class' => 'inline h-4 w-4 text-gray-900 dark:text-gray-50'])
                    </span>
                </button>
                <div x-show="open" class="mt-2 space-y-2 text-sm">
                    <p class="text-gray-600 dark:text-gray-300">
                        <span class="font-medium">{{ __('Server') }}:</span>
                        {{ $backupTask->remoteServer->label }}
                    </p>
                    <p class="text-gray-500 dark:text-gray-400">
                        <span class="font-medium">{{ __('Destination') }}:</span>
                        {{ $backupTask->backupDestination->label }}
                        ({{ $backupTask->backupDestination->type() }})
                    </p>
                    <p class="text-gray-500 dark:text-gray-400">
                        <span class="font-medium">{{ __('Scheduled') }}:</span>
                        @if ($backupTask->isPaused())
                            {{ __('N/A') }}
                        @else
                            @if ($backupTask->usingCustomCronExpression())
                                {{ $backupTask->custom_cron_expression }}
                            @else
                                {{ ucfirst(__($backupTask->frequency)) }}
                                {{ __('at') }}
                                {{ $backupTask->runTimeFormatted(Auth::user()) }}
                            @endif
                        @endif
                    </p>
                    <p class="text-gray-500 dark:text-gray-400">
                        <span class="font-medium">{{ __('Last ran') }}:</span>
                        {{ $backupTask->lastRunFormatted(Auth::user()) ?? __('Never') }}
                    </p>
                </div>
            </div>

            <!-- Actions - Always visible, but reorganized for responsive view -->
            <div class="mt-3 flex flex-wrap justify-start space-x-2">
                <livewire:backup-tasks.buttons.run-task-button
                    :$backupTask
                    :wire:key="'run-task-button-' . $backupTask->id"
                />

                <x-secondary-button
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'backup-task-{{ $backupTask->id }}')"
                    class="!p-2"
                    :disabled="!$backupTask->logs()->exists()"
                    :title="$backupTask->logs()->exists() ? __('Click to view this log') : __('No log available')"
                >
                    @svg('hugeicons-license', 'h-4 w-4')
                    <span class="sr-only">{{ __('View Log') }}</span>
                </x-secondary-button>

                <livewire:backup-tasks.buttons.toggle-pause-button
                    :backupTask="$backupTask"
                    :wire:key="'toggle-pause-button-' . $backupTask->id"
                />

                <a href="{{ route('backup-tasks.edit', $backupTask) }}" wire:navigate>
                    <x-secondary-button class="!p-2">
                        <span class="sr-only">
                            {{ __('Update Backup Task') }}
                        </span>
                        <x-hugeicons-task-edit-01 class="h-4 w-4" />
                    </x-secondary-button>
                </a>
            </div>
        </div>

        <!-- Large View (Grid) -->
        <div class="hidden grid-cols-12 items-center gap-4 md:grid">
            <!-- Task Type and Label -->
            <div class="col-span-12 flex items-center space-x-3 md:col-span-3">
                <div class="flex-shrink-0">
                    @if ($backupTask->isFilesType())
                        <div class="group relative" title="{{ __('Files Task') }}">
                            <div
                                class="absolute inset-0 rotate-3 transform rounded-lg bg-gradient-to-br from-purple-300 to-purple-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105 dark:from-purple-600 dark:to-purple-700"
                            ></div>
                            <div
                                class="relative rounded-lg bg-white p-2 shadow-sm transition-all duration-300 group-hover:shadow-md dark:bg-gray-800"
                            >
                                @svg(
                                    'hugeicons-file-01',
                                    'h-5 w-5 text-purple-600
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                dark:text-purple-400'
                                )
                            </div>
                        </div>
                    @elseif ($backupTask->isDatabaseType())
                        <div class="group relative" title="{{ __('Database Task') }}">
                            <div
                                class="absolute inset-0 rotate-3 transform rounded-lg bg-gradient-to-br from-cyan-300 to-cyan-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105 dark:from-cyan-600 dark:to-cyan-700"
                            ></div>
                            <div
                                class="relative rounded-lg bg-white p-2 shadow-sm transition-all duration-300 group-hover:shadow-md dark:bg-gray-800"
                            >
                                @svg('hugeicons-database', 'h-5 w-5 text-cyan-600 dark:text-cyan-400')
                            </div>
                        </div>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $backupTask->label }}
                    </h3>
                    <div class="ms-1.5 mt-1.5 flex space-x-2">
                        @if ($backupTask->tags()->exists())
                            <div
                                x-data
                                x-on:mouseenter="
                                    $dispatch('show-tags-tooltip', {
                                        id: {{ $backupTask->id }},
                                        tags: '{{ $backupTask->listOfAttachedTagLabels() }}',
                                    })
                                "
                            >
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    @svg('hugeicons-tags', 'mr-1 h-3 w-3 text-gray-500 dark:text-gray-400')
                                    {{ $backupTask->tags->count() }}
                                </span>
                            </div>
                        @endif

                        @if ($backupTask->notificationStreams()->exists())
                            <div
                                x-data
                                x-on:mouseenter="
                                    $dispatch('show-tags-tooltip', {
                                        id: {{ $backupTask->id }},
                                        tags: '{{ $backupTask->listOfAttachedTagLabels() }}',
                                    })
                                "
                            >
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    @svg('hugeicons-notification-02', 'mr-1 h-3 w-3 text-gray-500 dark:text-gray-400')
                                    {{ $backupTask->notificationStreams->count() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Server and Destination -->
            <div class="col-span-12 text-sm md:col-span-3">
                <p class="text-gray-600 dark:text-gray-300">
                    {{ $backupTask->remoteServer->label }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $backupTask->backupDestination->label }}
                    ({{ $backupTask->backupDestination->type() }})
                </p>
            </div>

            <!-- Status -->
            <div class="col-span-6 text-sm font-medium md:col-span-2">
                @if ($backupTask->isPaused())
                    <span
                        class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-800 dark:text-red-100"
                    >
                        @svg('hugeicons-pause', 'mr-1 h-3.5 w-3.5')
                        {{ __('Paused') }}
                    </span>
                @else
                    <span
                        class="{{ $backupTask->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    >
                        @if ($backupTask->status === 'ready')
                            @svg('hugeicons-checkmark-circle-02', 'mr-1 h-3.5 w-3.5')
                        @else
                            @svg('hugeicons-refresh', 'mr-1 h-3.5 w-3.5 animate-spin')
                        @endif
                        {{ $backupTask->status === 'ready' ? __('Ready') : __('Running') }}
                    </span>
                @endif
            </div>

            <!-- Schedule -->
            <div class="col-span-6 text-xs text-gray-500 md:col-span-2 dark:text-gray-400">
                <p>
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Scheduled') }}:</span>
                    @if ($backupTask->isPaused())
                        {{ __('N/A') }}
                    @else
                        @if ($backupTask->usingCustomCronExpression())
                            {{ $backupTask->custom_cron_expression }}
                        @else
                            {{ ucfirst(__($backupTask->frequency)) }}
                            {{ __('at') }}
                            {{ $backupTask->runTimeFormatted(Auth::user()) }}
                        @endif
                    @endif
                </p>
                <p class="mt-1">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Last ran') }}:</span>
                    {{ $backupTask->lastRunFormatted(Auth::user()) ?? __('Never') }}
                </p>
            </div>

            <!-- Actions -->
            <div class="col-span-12 flex justify-end space-x-2 md:col-span-2">
                <livewire:backup-tasks.buttons.run-task-button
                    :$backupTask
                    :wire:key="'run-task-button-large-' . $backupTask->id"
                />

                <x-secondary-button
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'backup-task-{{ $backupTask->id }}')"
                    class="!p-2"
                    :disabled="!$backupTask->logs()->exists()"
                    :title="$backupTask->logs()->exists() ? __('Click to view this log') : __('No log available')"
                >
                    @svg('hugeicons-license', 'h-4 w-4')
                    <span class="sr-only">{{ __('View Log') }}</span>
                </x-secondary-button>

                <livewire:backup-tasks.buttons.toggle-pause-button
                    :backupTask="$backupTask"
                    :wire:key="'toggle-pause-button-large-' . $backupTask->id"
                />

                <a href="{{ route('backup-tasks.edit', $backupTask) }}" wire:navigate>
                    <x-secondary-button class="!p-2">
                        <span class="sr-only">
                            {{ __('Update Backup Task') }}
                        </span>
                        <x-hugeicons-task-edit-01 class="h-4 w-4" />
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </div>
    <livewire:backup-tasks.modals.log-modal :backupTask="$backupTask" :key="'show-log-modal-' . $backupTask->id" />
</div>
