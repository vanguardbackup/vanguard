<div>
    <div class="mt-4">
        @if ($filteredCount === 0 &&Auth::user()->backupTasks()->exists())
            <div class="mb-4">
                <x-no-content withBackground>
                    <x-slot name="icon">
                        @svg('hugeicons-filter', 'inline h-16 w-16 text-primary-900 dark:text-white')
                    </x-slot>
                    <x-slot name="title">
                        {{ __('No backup tasks match your filters!') }}
                    </x-slot>
                    <x-slot name="description">
                        {{ __('Try adjusting your filter criteria or clear your filter.') }}
                    </x-slot>
                    <x-slot name="action">
                        <x-danger-button type="button" class="mt-4" wire:click="resetFilters">
                            {{ __('Reset Filters') }}
                        </x-danger-button>
                    </x-slot>
                </x-no-content>
            </div>
        @elseif (! Auth::user()->backupTasks()->exists())
            <x-no-content withBackground>
                <x-slot name="icon">
                    @svg('hugeicons-archive-02', 'inline h-16 w-16 text-primary-900 dark:text-white')
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
            <x-table.table-wrapper
                title="{{ __('Backup Tasks') }}"
                description="{{ __('An overview of all configured backup tasks along with their current statuses.') }}"
            >
                <x-slot name="icon">
                    <x-hugeicons-archive-02 class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                </x-slot>

                <div class="mb-6">
                    <div
                        class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="mb-4 flex items-center">
                            <x-hugeicons-filter class="mr-2 h-5 w-5 text-primary-600 dark:text-primary-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Filter Tasks') }}</h3>
                            @if ($selectedTag || $status || $search)
                                <div class="ml-auto">
                                    <x-secondary-button
                                        wire:click="resetFilters"
                                        class="flex items-center text-sm"
                                        title="{{ __('Clear all filters') }}"
                                    >
                                        @svg('hugeicons-filter-remove', 'mr-1 h-4 w-4')
                                        <span>{{ __('Clear Filters') }}</span>
                                    </x-secondary-button>
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div>
                                <x-input-label for="search" :value="__('Search')" class="mb-1 font-medium" />
                                <x-text-input
                                    id="search"
                                    name="search"
                                    type="text"
                                    class="block w-full pl-10"
                                    wire:model.live="search"
                                    :placeholder="__('Search by label')"
                                />
                            </div>

                            <div>
                                <x-input-label for="status" :value="__('Status')" class="mb-1 font-medium" />
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        @svg('hugeicons-checkmark-circle-02', 'h-4 w-4 text-gray-500')
                                    </div>
                                    <x-select
                                        id="status"
                                        class="block w-full pl-10"
                                        wire:model.live="status"
                                        name="status"
                                    >
                                        <option value="">{{ __('All Statuses') }}</option>
                                        @foreach ($statuses as $statusOption)
                                            <option value="{{ $statusOption }}">
                                                {{ __(ucfirst($statusOption)) }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                </div>
                            </div>

                            <div>
                                <x-input-label for="tag" :value="__('Tag')" class="mb-1 font-medium" />
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        @svg('hugeicons-tag-01', 'h-4 w-4 text-gray-500')
                                    </div>
                                    <x-select
                                        id="tag"
                                        name="tag"
                                        class="block w-full pl-10"
                                        wire:model.live="selectedTag"
                                    >
                                        <option value="">{{ __('All Tags') }}</option>
                                        @foreach ($tags as $tag)
                                            <option value="{{ $tag->id }}">{{ $tag->label }}</option>
                                        @endforeach
                                    </x-select>
                                </div>
                            </div>
                        </div>

                        @if ($selectedTag || $status || $search)
                            <div class="mt-4 border-t border-gray-200 pt-3 dark:border-gray-700">
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <span>{{ __('Active filters') }}:</span>

                                    @if ($search)
                                        <span
                                            class="ml-2 flex items-center rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-700"
                                        >
                                            {{ __('Search') }}: "{{ $search }}"
                                            <button
                                                wire:click="$set('search', '')"
                                                class="ml-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                            >
                                                @svg('hugeicons-cancel-01', 'h-3 w-3')
                                            </button>
                                        </span>
                                    @endif

                                    @if ($status)
                                        <span
                                            class="ml-2 flex items-center rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-700"
                                        >
                                            {{ __('Status') }}: {{ __(ucfirst($status)) }}
                                            <button
                                                wire:click="$set('status', '')"
                                                class="ml-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                            >
                                                @svg('hugeicons-cancel-01', 'h-3 w-3')
                                            </button>
                                        </span>
                                    @endif

                                    @if ($selectedTag)
                                        @php
                                            $selectedTagLabel = $tags->firstWhere('id', $selectedTag)->label ?? '';
                                        @endphp

                                        <span
                                            class="ml-2 flex items-center rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-700"
                                        >
                                            {{ __('Tag') }}: {{ $selectedTagLabel }}
                                            <button
                                                wire:click="$set('selectedTag', '')"
                                                class="ml-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                            >
                                                @svg('hugeicons-cancel-01', 'h-3 w-3')
                                            </button>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($backupTasks->isEmpty() && $filteredCount > 0)
                    <div class="mb-4">
                        <x-no-content>
                            <x-slot name="title">
                                {{ __('No backup tasks match your filters') }}
                            </x-slot>
                            <x-slot name="description">
                                {{ __('Try adjusting your search or filter criteria.') }}
                            </x-slot>
                        </x-no-content>
                    </div>
                @else
                    <x-table.table-header>
                        <div class="col-span-12 md:col-span-3">
                            {{ __('Task') }}
                        </div>
                        <div class="col-span-12 md:col-span-3">
                            {{ __('Server & Destination') }}
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            {{ __('Status & Schedule') }}
                        </div>
                        <div class="col-span-12 md:col-span-2">
                            {{ __('Actions') }}
                        </div>
                    </x-table.table-header>
                    <x-table.table-body>
                        @foreach ($backupTasks as $backupTask)
                            <livewire:backup-tasks.tables.index-item
                                :backupTask="$backupTask"
                                :key="'index-item-' . $backupTask->id"
                            />
                        @endforeach
                    </x-table.table-body>
                @endif
            </x-table.table-wrapper>
            <div class="mt-4 flex justify-end">
                {{ $backupTasks->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('urlParametersUpdated', ({ queryParams }) => {
            history.replaceState(null, '', `?${queryParams}`);
        });

        Livewire.on('urlParametersCleared', () => {
            history.replaceState(null, '', window.location.pathname);
        });
    });
</script>
