<div>
    @if ($notificationStreams->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('heroicon-o-bell', 'h-16 w-16 text-primary-900 dark:text-white inline')
            </x-slot>
            <x-slot name="title">
                {{ __('No Notification Streams') }}
            </x-slot>
            <x-slot name="description">
                {{ __('You do not have any notification streams configured.') }}
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('notification-streams.create') }}" wire:navigate>
                    <x-primary-button type="button" class="mt-4">
                        {{ __('Add Notification Stream') }}
                    </x-primary-button>
                </a>
            </x-slot>
        </x-no-content>
    @else
        <x-table.table-wrapper
            title="{{ __('Notification Streams') }}"
            description="{{ __('Configured Notification streams for your backup tasks.') }}">
            <x-slot name="icon">
                <x-heroicon-o-bell class="h-6 w-6 text-primary-600 dark:text-primary-400"/>
            </x-slot>
            <x-table.table-header>
                <div class="col-span-3">{{ __('Label') }}</div>
                <div class="col-span-3">{{ __('Used') }}</div>
                <div class="col-span-3">{{ __('Type') }}</div>
                <div class="col-span-3">{{ __('Actions') }}</div>
            </x-table.table-header>
            <x-table.table-body>
                @foreach ($notificationStreams as $notificationStream)
                    <x-table.table-row>
                        <div class="col-span-12 sm:col-span-3 flex flex-col sm:flex-row sm:items-center">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $notificationStream->label }}</p>
                        </div>

                        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0">
                            <span class="text-gray-600 dark:text-gray-300">
                               {{ trans_choice('{0} Not Used|{1} Once|[2,*] :count Times', $notificationStream->backupTasks?->count() ?? 0, ['count' => $notificationStream->backupTasks?->count() ?? 0]) }}
                            </span>
                        </div>

                        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0">
                            <div class="inline text-gray-600 dark:text-gray-300">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"
                                     fill="currentColor" class="inline h-4 w-4 mr-1">
                                    <path d="{{ $notificationStream->type_icon }}"/>
                                </svg>
                                {{ $notificationStream->formatted_type }}
                            </div>
                        </div>

                        <div
                            class="col-span-12 sm:col-span-3 mt-4 sm:mt-0 flex justify-start sm:justify-center space-x-2">
                            <a href="{{ route('notification-streams.edit', $notificationStream) }}" wire:navigate>
                                <x-secondary-button iconOnly>
                                    <span class="sr-only">{{ __('Update Notification Stream') }}</span>
                                    <x-heroicon-o-pencil-square class="w-4 h-4"/>
                                </x-secondary-button>
                            </a>
                        </div>
                    </x-table.table-row>
                @endforeach
            </x-table.table-body>
        </x-table.table-wrapper>
    @endif
</div>
