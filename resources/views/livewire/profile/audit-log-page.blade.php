<div>
    @section('title', __('Audit Logs'))
    <x-slot name="header">
        {{ __('Audit Logs') }}
    </x-slot>
    <div>
        @if ($this->hasAuditLogs())
            <x-table.table-wrapper
                title="{{ __('Audit Logs') }}"
                description="{{ __('View all actions performed on your account.') }}">
                <x-slot name="icon">
                    <x-hugeicons-license class="h-6 w-6 text-primary-600 dark:text-primary-400"/>
                </x-slot>
                <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4 mb-4">
                    <!-- Search Input -->
                    <div class="w-full sm:w-1/2">
                        <x-text-input
                            name="search"
                            id="search"
                            class="block w-full"
                            type="text"
                            wire:model.live="search"
                            :placeholder="__('Search audit logs...')"
                        />
                    </div>
                    <!-- Export Options -->
                    <div class="flex items-center space-x-2">
                        <x-select
                            name="exportFormat"
                            wire:model="exportFormat"
                            id="exportFormat">
                            <option value="csv">CSV</option>
                            <option value="json">JSON</option>
                        </x-select>
                        <x-secondary-button wire:click="export" class="mt-2.5">
                            {{ __('Export') }}
                            @svg('hugeicons-download-04', 'h-4 w-4 ml-2')
                        </x-secondary-button>
                    </div>
                </div>

                @if ($auditLogs->isEmpty())
                    <x-table.table-body>
                        <x-table.table-row>
                            <div class="col-span-12 py-4 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No audit logs found matching your criteria.') }}
                            </div>
                        </x-table.table-row>
                    </x-table.table-body>
                @else
                    <x-table.table-header>
                        <div class="col-span-4">{{ __('Action') }}</div>
                        <div class="col-span-4">{{ __('User') }}</div>
                        <div class="col-span-3">{{ __('Date') }}</div>
                        <div class="col-span-1">{{ __('Actions') }}</div>
                    </x-table.table-header>
                    <x-table.table-body>
                        @foreach ($auditLogs as $log)
                            <x-table.table-row>
                                <div class="col-span-4 flex flex-col sm:flex-row sm:items-center">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $log->message }}</p>
                                </div>

                                <div class="col-span-4 mt-2 sm:mt-0 flex items-center">
                                    <img src="{{ $log->gravatar_url }}" alt="{{ $log->user_name }}"
                                         class="w-10 h-10 rounded-full mr-3">
                                    <span class="text-sm text-gray-800 dark:text-gray-200 truncate block">
                                        {{ $log->user_name }}
                                    </span>
                                </div>

                                <div class="col-span-3 mt-2 sm:mt-0">
                                    <span class="inline-flex items-center text-sm text-gray-800 dark:text-gray-100">
                                        {{ Carbon\Carbon::parse($log->created_at)->timezone(auth()->user()->timezone)->locale(auth()->user()->language ?? app()->getLocale())->isoFormat('MMMM D, YYYY') }}
                                    </span>
                                </div>

                                <!-- View Details Button -->
                                <div class="col-span-1 mt-2 sm:mt-0 flex justify-end">
                                    <x-secondary-button wire:click="selectLog({{ $log->id }})" iconOnly>
                                        <span class="sr-only">{{ __('View Details') }}</span>
                                        <x-hugeicons-right-to-left-list-triangle class="w-4 h-4"/>
                                    </x-secondary-button>
                                </div>
                            </x-table.table-row>
                        @endforeach
                    </x-table.table-body>
                @endif
            </x-table.table-wrapper>

            <div class="mt-4 flex justify-end">
                {{ $auditLogs->links() }}
            </div>
        @else
            <x-no-content withBackground>
                <x-slot name="icon">
                    @svg('hugeicons-license', 'h-16 w-16 text-primary-900 dark:text-white inline')
                </x-slot>
                <x-slot name="title">
                    {{ __("You don't have any audit logs yet!") }}
                </x-slot>
                <x-slot name="description">
                    {{ __("Audit logs help you keep track of important actions in your account. They will appear here as you use the application.") }}
                </x-slot>
            </x-no-content>
        @endif

        <!-- Audit Log Details Modal -->
        <x-modal name="audit-log-details" :show="$selectedLog !== null" focusable>
            <x-slot name="title">
                {{ __('Audit Log Details') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Detailed information about the selected audit log entry.') }}
            </x-slot>
            <x-slot name="icon">
                hugeicons-license
            </x-slot>
            @if ($selectedLog)
                <div class="space-y-6">
                    <x-notice type="info">
                        {{ __('This log entry records a specific action taken on your account. Review the details below for a comprehensive understanding of the event.') }}
                    </x-notice>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                    @svg('hugeicons-settings-02', 'w-5 h-5 mr-2 text-gray-500 dark:text-gray-400')
                                    {{ __('Action') }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 pl-7">{{ $selectedLog->message }}</p>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                    @svg('hugeicons-user', 'w-5 h-5 mr-2 text-gray-500 dark:text-gray-400')
                                    {{ __('User') }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 pl-7">{{ $selectedLog->user_name }}</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                    @svg('hugeicons-calendar-01', 'w-5 h-5 mr-2 text-gray-500 dark:text-gray-400')
                                    {{ __('Date and Time') }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 pl-7">
                                    {{ Carbon\Carbon::parse($selectedLog->created_at)->timezone(auth()->user()->timezone)->locale(auth()->user()->language ?? app()->getLocale())->isoFormat('MMMM D, YYYY HH:mm:ss') }}
                                </p>
                            </div>
                            @if ($selectedLog->ip_address)
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                        @svg('hugeicons-globe', 'w-5 h-5 mr-2 text-gray-500 dark:text-gray-400')
                                        {{ __('IP Address') }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 pl-7">{{ $selectedLog->ip_address }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($selectedLog->context)
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center mb-3">
                                @svg('hugeicons-profile', 'w-5 h-5 mr-2 text-gray-500 dark:text-gray-400')
                                {{ __('Changes') }}
                            </h3>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Field') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Change') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($selectedLog->context as $field => $change)
                                        <tr>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $field }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">
                                                @if (is_array($change))
                                                    <pre class="whitespace-pre-wrap">{{ json_encode($change, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                @else
                                                    {{ $change }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            <div class="mt-6">
                <x-secondary-button wire:click="clearSelectedLog" centered>
                    {{ __('Close') }}
                </x-secondary-button>
            </div>
        </x-modal>
    </div>
</div>
