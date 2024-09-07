<div>
    <div class="mt-4">
        @if ($filteredCount === 0 &&Auth::user()->backupTasks()->exists())
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

                <div class="mb-4 flex flex-wrap items-center gap-4">
                    <div class="flex-grow">
                        <x-input-label for="search" :value="__('Search')" />
                        <x-text-input
                            id="search"
                            name="search"
                            type="text"
                            class="mt-1 block w-full"
                            wire:model.live="search"
                            :placeholder="__('Search by label')"
                        />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <x-select id="status" class="mt-1 block w-full" wire:model.live="status" name="status">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($statuses as $statusOption)
                                <option value="{{ $statusOption }}">{{ __(ucfirst($statusOption)) }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div>
                        <x-input-label for="tag" :value="__('Tag')" />
                        <x-select id="tag" name="tag" class="mt-1 block w-full" wire:model.live="selectedTag">
                            <option value="">{{ __('All Tags') }}</option>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->label }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="mt-7 flex items-end">
                        <x-secondary-button wire:click="resetFilters" iconOnly title="{{ __('Clear filter') }}">
                            @svg('hugeicons-filter-remove')
                        </x-secondary-button>
                    </div>
                </div>

                @if ($backupTasks->isEmpty() && $filteredCount > 0)
                    <x-no-content>
                        <x-slot name="title">
                            {{ __('No backup tasks match your filters') }}
                        </x-slot>
                        <x-slot name="description">
                            {{ __('Try adjusting your search or filter criteria.') }}
                        </x-slot>
                    </x-no-content>
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
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('urlParametersCleared', () => {
                history.replaceState(null, '', window.location.pathname);
            });
        });
    </script>
</div>
