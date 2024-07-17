<div class="bg-white dark:bg-gray-800 rounded-none transition-all duration-300 overflow-hidden mb-4">
    <div class="p-3 space-y-4">
        <!-- Responsive View (Collapsible) -->
        <div class="md:hidden">
            <!-- Task Type, Label, and Status - Always visible -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        @if ($backupTask->isFilesType())
                            <div class="relative group" title="{{ __('Files Task') }}">
                                <div
                                    class="absolute inset-0 bg-gradient-to-br from-purple-300 to-purple-400 dark:from-purple-600 dark:to-purple-700 rounded-lg transform rotate-3 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105"></div>
                                <div
                                    class="relative bg-white dark:bg-gray-800 rounded-lg p-2 shadow-sm transition-all duration-300 group-hover:shadow-md">
                                    @svg('heroicon-o-document-duplicate', 'h-5 w-5 text-purple-600
                                    dark:text-purple-400')
                                </div>
                            </div>
                        @elseif ($backupTask->isDatabaseType())
                            <div class="relative group" title="{{ __('Database Task') }}">
                                <div
                                    class="absolute inset-0 bg-gradient-to-br from-cyan-300 to-cyan-400 dark:from-cyan-600 dark:to-cyan-700 rounded-lg transform rotate-3 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105"></div>
                                <div
                                    class="relative bg-white dark:bg-gray-800 rounded-lg p-2 shadow-sm transition-all duration-300 group-hover:shadow-md">
                                    @svg('heroicon-o-circle-stack', 'h-5 w-5 text-cyan-600 dark:text-cyan-400')
                                </div>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $backupTask->label }}</h3>
                        <div class="flex space-x-2">
                            @if ($backupTask->tags()->exists())
                                <div x-data
                                     x-on:mouseenter="$dispatch('show-tags-tooltip', { id: {{ $backupTask->id }}, tags: '{{ $backupTask->listOfAttachedTagLabels() }}' })">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        @svg('heroicon-o-tag', 'h-3 w-3 mr-1 text-gray-500 dark:text-gray-400')
                                        {{ $backupTask->tags->count() }}
                                    </span>
                                </div>
                            @endif

                            @if ($backupTask->notificationStreams()->exists())
                                <div x-data
                                     x-on:mouseenter="$dispatch('show-tags-tooltip', { id: {{ $backupTask->id }}, tags: '{{ $backupTask->listOfAttachedTagLabels() }}' })">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            @svg('heroicon-o-bell', 'h-3 w-3 mr-1 text-gray-500 dark:text-gray-400')
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
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                            @svg('heroicon-o-pause', 'h-3.5 w-3.5 mr-1')
                            {{ __('Paused') }}
                        </span>
                    @else
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $backupTask->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' }}">
                            @svg($backupTask->status === 'ready' ? 'heroicon-o-check-circle' : 'heroicon-o-arrow-path', 'h-3.5 w-3.5 mr-1')
                            {{ $backupTask->status === 'ready' ? __('Ready') : __('Running') }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Server, Destination, and Schedule - Collapsible Responsively -->
            <div x-data="{ open: false }" class="my-3">
                <button @click="open = !open"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 focus:outline-none">
                    {{ __('Details') }}
                    <span x-show="!open">▼</span>
                    <span x-show="open">▲</span>
                </button>
                <div x-show="open" class="mt-2 space-y-2 text-sm">
                    <p class="text-gray-600 dark:text-gray-300">
                        <span class="font-medium">{{ __('Server') }}:</span> {{ $backupTask->remoteServer->label }}
                    </p>
                    <p class="text-gray-500 dark:text-gray-400">
                        <span
                            class="font-medium">{{ __('Destination') }}:</span> {{ $backupTask->backupDestination->label }}
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
                                {{ ucfirst(__($backupTask->frequency)) }} {{ __('at') }} {{ $backupTask->runTimeFormatted(Auth::user()) }}
                            @endif
                        @endif
                    </p>
                    <p class="text-gray-500 dark:text-gray-400">
                        <span
                            class="font-medium">{{ __('Last ran') }}:</span> {{ $backupTask->lastRunFormatted(Auth::user()) }}
                    </p>
                </div>
            </div>

            <!-- Actions - Always visible, but reorganized for responsive view -->
            <div class="flex flex-wrap justify-start space-x-2 space-y-2 sm:space-y-0 sm:justify-end">
                <livewire:backup-tasks.buttons.run-task-button :$backupTask
                                                               :key="'run-task-button-' . $backupTask->id"/>

                <x-secondary-button
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'backup-task-{{ $backupTask->id }}')"
                    iconOnly
                    :disabled="!$backupTask->logs()->exists()"
                    :title="$backupTask->logs()->exists() ? __('Click to view this log') : __('No log available')"
                >
                    @svg('heroicon-o-document-text', 'h-4 w-4')
                    <span class="sr-only">{{ __('View Log') }}</span>
                </x-secondary-button>

                <livewire:backup-tasks.buttons.toggle-pause-button
                    :backupTask="$backupTask"
                    :key="'toggle-pause-button-' . $backupTask->id"
                />

                <a href="{{ route('backup-tasks.edit', $backupTask) }}" wire:navigate>
                    <x-secondary-button iconOnly>
                        <span class="sr-only">{{ __('Update Backup Task') }}</span>
                        <x-heroicon-o-pencil-square class="w-4 h-4"/>
                    </x-secondary-button>
                </a>
            </div>
        </div>

        <!-- Large View (Grid) -->
        <div class="hidden md:grid grid-cols-12 gap-4 items-center">
            <!-- Task Type and Label -->
            <div class="col-span-12 md:col-span-3 flex items-center space-x-3">
                <div class="flex-shrink-0">
                    @if ($backupTask->isFilesType())
                        <div class="relative group" title="{{ __('Files Task') }}">
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-purple-300 to-purple-400 dark:from-purple-600 dark:to-purple-700 rounded-lg transform rotate-3 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105"></div>
                            <div
                                class="relative bg-white dark:bg-gray-800 rounded-lg p-2 shadow-sm transition-all duration-300 group-hover:shadow-md">
                                @svg('heroicon-o-document-duplicate', 'h-5 w-5 text-purple-600 dark:text-purple-400')
                            </div>
                        </div>
                    @elseif ($backupTask->isDatabaseType())
                        <div class="relative group" title="{{ __('Database Task') }}">
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-cyan-300 to-cyan-400 dark:from-cyan-600 dark:to-cyan-700 rounded-lg transform rotate-3 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105"></div>
                            <div
                                class="relative bg-white dark:bg-gray-800 rounded-lg p-2 shadow-sm transition-all duration-300 group-hover:shadow-md">
                                @svg('heroicon-o-circle-stack', 'h-5 w-5 text-cyan-600 dark:text-cyan-400')
                            </div>
                        </div>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $backupTask->label }}</h3>
                    <div class="flex space-x-2 mt-1.5 ms-1.5">
                        @if ($backupTask->tags()->exists())
                            <div x-data
                                 x-on:mouseenter="$dispatch('show-tags-tooltip', { id: {{ $backupTask->id }}, tags: '{{ $backupTask->listOfAttachedTagLabels() }}' })">
            <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                @svg('heroicon-o-tag', 'h-3 w-3 mr-1 text-gray-500 dark:text-gray-400')
                {{ $backupTask->tags->count() }}
            </span>
                            </div>
                        @endif

                        @if ($backupTask->notificationStreams()->exists())
                            <div x-data
                                 x-on:mouseenter="$dispatch('show-tags-tooltip', { id: {{ $backupTask->id }}, tags: '{{ $backupTask->listOfAttachedTagLabels() }}' })">
            <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                @svg('heroicon-o-bell', 'h-3 w-3 mr-1 text-gray-500 dark:text-gray-400')
                {{ $backupTask->notificationStreams->count() }}
            </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Server and Destination -->
            <div class="col-span-12 md:col-span-3 text-sm">
                <p class="text-gray-600 dark:text-gray-300">{{ $backupTask->remoteServer->label }}</p>
                <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">{{ $backupTask->backupDestination->label }}
                    ({{ $backupTask->backupDestination->type() }})</p>
            </div>

            <!-- Status -->
            <div class="col-span-6 md:col-span-2 text-sm font-medium">
                @if ($backupTask->isPaused())
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                        @svg('heroicon-o-pause', 'h-3.5 w-3.5 mr-1')
                        {{ __('Paused') }}
                    </span>
                @else
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $backupTask->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' }}">
                        @svg($backupTask->status === 'ready' ? 'heroicon-o-check-circle' : 'heroicon-o-arrow-path', 'h-3.5 w-3.5 mr-1')
                        {{ $backupTask->status === 'ready' ? __('Ready') : __('Running') }}
                    </span>
                @endif
            </div>

            <!-- Schedule -->
            <div class="col-span-6 md:col-span-2 text-xs text-gray-500 dark:text-gray-400">
                <p>
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Scheduled') }}:</span>
                    @if ($backupTask->isPaused())
                        {{ __('N/A') }}
                    @else
                        @if ($backupTask->usingCustomCronExpression())
                            {{ $backupTask->custom_cron_expression }}
                        @else
                            {{ ucfirst(__($backupTask->frequency)) }} {{ __('at') }} {{ $backupTask->runTimeFormatted(Auth::user()) }}
                        @endif
                    @endif
                </p>
                <p class="mt-1">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Last ran') }}:</span>
                    {{ $backupTask->lastRunFormatted(Auth::user()) }}
                </p>
            </div>

            <!-- Actions -->
            <div class="col-span-12 md:col-span-2 flex justify-end space-x-2">
                <livewire:backup-tasks.buttons.run-task-button :$backupTask
                                                               :key="'run-task-button-' . $backupTask->id"/>

                <x-secondary-button
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'backup-task-{{ $backupTask->id }}')"
                    iconOnly
                    :disabled="!$backupTask->logs()->exists()"
                    :title="$backupTask->logs()->exists() ? __('Click to view this log') : __('No log available')"
                >
                    @svg('heroicon-o-document-text', 'h-4 w-4')
                    <span class="sr-only">{{ __('View Log') }}</span>
                </x-secondary-button>

                <livewire:backup-tasks.buttons.toggle-pause-button
                    :backupTask="$backupTask"
                    :key="'toggle-pause-button-' . $backupTask->id"
                />

                <a href="{{ route('backup-tasks.edit', $backupTask) }}" wire:navigate>
                    <x-secondary-button iconOnly>
                        <span class="sr-only">{{ __('Update Backup Task') }}</span>
                        <x-heroicon-o-pencil-square class="w-4 h-4"/>
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </div>
    <livewire:backup-tasks.modals.log-modal :backupTask="$backupTask" :key="'show-log-modal-' . $backupTask->id"/>
</div>
