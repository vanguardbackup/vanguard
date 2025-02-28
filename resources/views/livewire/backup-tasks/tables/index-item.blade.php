<div class="mb-4 rounded-lg bg-white shadow-sm transition-all duration-300 dark:bg-gray-800">
    <div class="space-y-4 p-4">
        <!-- Mobile View (Collapsible) -->
        <div class="md:hidden">
            <!-- Task Type, Label, and Status -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        @if ($backupTask->isFilesType())
                            <div class="group relative" title="{{ __('Files Task') }}">
                                <div class="absolute inset-0 rotate-3 transform rounded-lg bg-gradient-to-br from-purple-300 to-purple-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105 dark:from-purple-600 dark:to-purple-700"></div>
                                <div class="relative rounded-lg bg-white p-2 shadow-sm transition-all duration-300 group-hover:shadow-md dark:bg-gray-800">
                                    @svg('hugeicons-file-01', 'h-5 w-5 text-purple-600 dark:text-purple-400')
                                </div>
                            </div>
                        @elseif ($backupTask->isDatabaseType())
                            <div class="group relative" title="{{ __('Database Task') }}">
                                <div class="absolute inset-0 rotate-3 transform rounded-lg bg-gradient-to-br from-cyan-300 to-cyan-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105 dark:from-cyan-600 dark:to-cyan-700"></div>
                                <div class="relative rounded-lg bg-white p-2 shadow-sm transition-all duration-300 group-hover:shadow-md dark:bg-gray-800">
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
                                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'view-tags-{{ $backupTask->id }}')" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                    @svg('hugeicons-tags', 'mr-1 h-3 w-3 text-gray-500 dark:text-gray-400')
                                    {{ $backupTask->tags->count() }}
                                </button>
                            @endif

                            @if ($backupTask->notificationStreams()->exists())
                                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'view-notification-streams-{{ $backupTask->id }}')" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                    @svg('hugeicons-notification-02', 'mr-1 h-3 w-3 text-gray-500 dark:text-gray-400')
                                    {{ $backupTask->notificationStreams->count() }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="text-sm font-medium">
                    @if ($backupTask->isPaused())
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-800 dark:text-red-100">
                            @svg('hugeicons-pause', 'mr-1 h-3.5 w-3.5')
                            {{ __('Paused') }}
                        </span>
                    @else
                        <span class="{{ $backupTask->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
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

            <!-- Server, Destination, and Schedule - Collapsible -->
            <div x-data="{ open: false }" class="my-3">
                <button @click="open = !open" class="text-sm text-gray-600 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:text-gray-100">
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

            <!-- Actions - Grouped as Action Menu -->
            <div class="mt-3 flex flex-wrap items-center space-x-2">
                <!-- Primary Actions - Always visible -->
                <livewire:backup-tasks.buttons.run-task-button
                    :$backupTask
                    :wire:key="'run-task-button-' . $backupTask->id"
                />

                <livewire:backup-tasks.buttons.toggle-pause-button
                    :backupTask="$backupTask"
                    :wire:key="'toggle-pause-button-' . $backupTask->id"
                />

                <!-- Secondary Actions - Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <x-secondary-button @click="open = !open" class="!p-2">
                        @svg('hugeicons-more-vertical-circle-01', 'h-4 w-4')
                        <span class="sr-only">{{ __('More Actions') }}</span>
                    </x-secondary-button>

                    <div x-show="open" @click.away="open = false" class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700">
                        <!-- View Log -->
                        <button
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'backup-task-{{ $backupTask->id }}'); open = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 dark:text-gray-200 dark:hover:bg-gray-600"
                            :disabled="!$backupTask->logs()->exists()"
                        >
                            @svg('hugeicons-license', 'mr-2 h-4 w-4')
                            {{ __('View Log') }}
                        </button>

                        <!-- View Webhook URL -->
                        <button
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'webhook-modal-{{ $backupTask->id }}'); open = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            @svg('hugeicons-link-03', 'mr-2 h-4 w-4')
                            {{ __('Webhook URL') }}
                        </button>

                        <!-- Edit -->
                        <a
                            href="{{ route('backup-tasks.edit', $backupTask) }}"
                            wire:navigate
                            class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            @svg('hugeicons-task-edit-01', 'mr-2 h-4 w-4')
                            {{ __('Edit Task') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop View (Grid) -->
        <div class="hidden md:grid grid-cols-12 items-center gap-4">
            <!-- Task Type and Label -->
            <div class="col-span-3 flex items-center space-x-3">
                <div class="flex-shrink-0">
                    @if ($backupTask->isFilesType())
                        <div class="group relative" title="{{ __('Files Task') }}">
                            <div class="absolute inset-0 rotate-3 transform rounded-lg bg-gradient-to-br from-purple-300 to-purple-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105 dark:from-purple-600 dark:to-purple-700"></div>
                            <div class="relative rounded-lg bg-white p-2 shadow-sm transition-all duration-300 group-hover:shadow-md dark:bg-gray-800">
                                @svg('hugeicons-file-01', 'h-5 w-5 text-purple-600 dark:text-purple-400')
                            </div>
                        </div>
                    @elseif ($backupTask->isDatabaseType())
                        <div class="group relative" title="{{ __('Database Task') }}">
                            <div class="absolute inset-0 rotate-3 transform rounded-lg bg-gradient-to-br from-cyan-300 to-cyan-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-105 dark:from-cyan-600 dark:to-cyan-700"></div>
                            <div class="relative rounded-lg bg-white p-2 shadow-sm transition-all duration-300 group-hover:shadow-md dark:bg-gray-800">
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
                            <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'view-tags-{{ $backupTask->id }}')" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                @svg('hugeicons-tags', 'mr-1 h-3 w-3 text-gray-500 dark:text-gray-400')
                                {{ $backupTask->tags->count() }}
                            </button>
                        @endif

                        @if ($backupTask->notificationStreams()->exists())
                            <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'view-notification-streams-{{ $backupTask->id }}')" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                @svg('hugeicons-notification-02', 'mr-1 h-3 w-3 text-gray-500 dark:text-gray-400')
                                {{ $backupTask->notificationStreams->count() }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Server and Destination -->
            <div class="col-span-3 text-sm">
                <p class="text-gray-600 dark:text-gray-300">
                    {{ $backupTask->remoteServer->label }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $backupTask->backupDestination->label }}
                    ({{ $backupTask->backupDestination->type() }})
                </p>
            </div>

            <!-- Status -->
            <div class="col-span-2 text-sm font-medium">
                @if ($backupTask->isPaused())
                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-800 dark:text-red-100">
                        @svg('hugeicons-pause', 'mr-1 h-3.5 w-3.5')
                        {{ __('Paused') }}
                    </span>
                @else
                    <span class="{{ $backupTask->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
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
            <div class="col-span-2 text-xs text-gray-500 dark:text-gray-400">
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

            <!-- Actions - Compact Action Bar -->
            <div class="col-span-2 flex items-center justify-end space-x-2">
                <!-- Primary Actions -->
                <livewire:backup-tasks.buttons.run-task-button
                    :$backupTask
                    :wire:key="'run-task-button-large-' . $backupTask->id"
                />

                <livewire:backup-tasks.buttons.toggle-pause-button
                    :backupTask="$backupTask"
                    :wire:key="'toggle-pause-button-large-' . $backupTask->id"
                />

                <livewire:backup-tasks.buttons.toggle-favourite-button
                    :backupTask="$backupTask"
                    :wire:key="'toggle-favourite-button-large-' . $backupTask->id"
                />

                <!-- Secondary Actions Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <x-secondary-button @click="open = !open" class="!p-2">
                        @svg('hugeicons-more-vertical-circle-01', 'h-4 w-4')
                        <span class="sr-only">{{ __('More Actions') }}</span>
                    </x-secondary-button>

                    <div style="position: absolute; z-index: 9999;"  x-show="open" @click.away="open = false" class="absolute right-0 z-[100] mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700">
                        <!-- View Log -->
                        <button
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'backup-task-{{ $backupTask->id }}'); open = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 dark:text-gray-200 dark:hover:bg-gray-600"
                            :disabled="!$backupTask->logs()->exists()"
                        >
                            @svg('hugeicons-license', 'mr-2 h-4 w-4')
                            {{ __('View Log') }}
                        </button>

                        <!-- View Webhook URL -->
                        <button
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'webhook-modal-{{ $backupTask->id }}'); open = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            @svg('hugeicons-link-03', 'mr-2 h-4 w-4')
                            {{ __('Webhook URL') }}
                        </button>

                        <!-- Edit -->
                        <a
                            href="{{ route('backup-tasks.edit', $backupTask) }}"
                            wire:navigate
                            class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            @svg('hugeicons-task-edit-01', 'mr-2 h-4 w-4')
                            {{ __('Edit Task') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Modal -->
    <livewire:backup-tasks.modals.log-modal :backupTask="$backupTask" :key="'show-log-modal-' . $backupTask->id" />

    <!-- Webhook URL Modal -->
    <livewire:backup-tasks.modals.webhook-modal :backupTask="$backupTask" :key="'show-webhook-modal-' . $backupTask->id" />

    <!-- Tags Modal -->
    <x-modal name="view-tags-{{ $backupTask->id }}" :key="'tags-modal-' . $backupTask->id" focusable>
        <x-slot name="title">
            {{ __('Tags for :task', ['task' => $backupTask->label]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('View tags associated with this backup task.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-tags</x-slot>
        <div class="max-h-96 space-y-4 overflow-y-auto">
            @forelse ($backupTask->tags as $tag)
                <div class="flex items-center justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $tag->label }}</span>
                </div>
            @empty
                <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                    {{ __('No tags assigned to this backup task.') }}
                </div>
            @endforelse
        </div>
        <div class="mt-6">
            <x-secondary-button x-on:click="$dispatch('close')" class="w-full justify-center">
                {{ __('Close') }}
            </x-secondary-button>
        </div>
    </x-modal>

    <!-- Notification Streams Modal -->
    <x-modal name="view-notification-streams-{{ $backupTask->id }}" :key="'notification-streams-modal-' . $backupTask->id" focusable>
        <x-slot name="title">
            {{ __('Notification Streams for :task', ['task' => $backupTask->label]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('View notification streams associated with this backup task.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-notification-02</x-slot>
        <div class="max-h-96 space-y-4 overflow-y-auto">
            @forelse ($backupTask->notificationStreams as $stream)
                <div class="flex items-center justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $stream->label }}</span>
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                        {{ $stream->type }}
                    </span>
                </div>
            @empty
                <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                    {{ __('No notification streams assigned to this backup task.') }}
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
