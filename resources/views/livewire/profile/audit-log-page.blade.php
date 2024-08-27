<div>
    @section('title', __('Audit Logs'))
    <x-slot name="header">
        {{ __('Audit Logs') }}
    </x-slot>
    <div>
        @if ($this->hasAuditLogs())
            <x-table.table-wrapper
                title="{{ __('Audit Logs') }}"
                description="{{ __('View all actions performed on your account.') }}"
            >
                <x-slot name="icon">
                    <x-hugeicons-license class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                </x-slot>
                <div
                    class="mb-4 flex flex-col items-center justify-between space-y-4 sm:flex-row sm:space-x-4 sm:space-y-0"
                >
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
                        <x-select name="exportFormat" wire:model="exportFormat" id="exportFormat">
                            <option value="csv">CSV</option>
                            <option value="json">JSON</option>
                        </x-select>
                        <x-secondary-button wire:click="export" class="mt-2.5">
                            {{ __('Export') }}
                            @svg('hugeicons-download-04', 'ml-2 h-4 w-4')
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
                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $log->message }}
                                    </p>
                                </div>

                                <div class="col-span-4 mt-2 flex items-center sm:mt-0">
                                    <img
                                        src="{{ $log->gravatar_url }}"
                                        alt="{{ $log->user_name }}"
                                        class="mr-3 h-10 w-10 rounded-full"
                                    />
                                    <span class="block truncate text-sm text-gray-800 dark:text-gray-200">
                                        {{ $log->user_name }}
                                    </span>
                                </div>

                                <div class="col-span-3 mt-2 sm:mt-0">
                                    <span class="inline-flex items-center text-sm text-gray-800 dark:text-gray-100">
                                        {{ Carbon\Carbon::parse($log->created_at)->timezone(auth()->user()->timezone)->locale(auth()->user()->language ?? app()->getLocale())->isoFormat('MMMM D, YYYY') }}
                                    </span>
                                </div>

                                <!-- View Details Button -->
                                <div class="col-span-1 mt-2 flex justify-end sm:mt-0">
                                    <x-secondary-button wire:click="selectLog({{ $log->id }})" iconOnly>
                                        <span class="sr-only">
                                            {{ __('View Details') }}
                                        </span>
                                        <x-hugeicons-right-to-left-list-triangle class="h-4 w-4" />
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
                    @svg('hugeicons-license', 'inline h-16 w-16 text-primary-900 dark:text-white')
                </x-slot>
                <x-slot name="title">
                    {{ __("You don't have any audit logs yet!") }}
                </x-slot>
                <x-slot name="description">
                    {{ __('Audit logs help you keep track of important actions in your account. They will appear here as you use the application.') }}
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
            <x-slot name="icon">hugeicons-license</x-slot>
            @if ($selectedLog)
                <div class="space-y-6">
                    <x-notice type="info">
                        {{ __('This log entry records a specific action taken on your account. Review the details below for a comprehensive understanding of the event.') }}
                    </x-notice>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-4">
                            <div>
                                <h3 class="flex items-center text-lg font-medium text-gray-900 dark:text-gray-100">
                                    @svg('hugeicons-settings-02', 'mr-2 h-5 w-5 text-gray-500 dark:text-gray-400')
                                    {{ __('Action') }}
                                </h3>
                                <p class="mt-1 pl-7 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $selectedLog->message }}
                                </p>
                            </div>
                            <div>
                                <h3 class="flex items-center text-lg font-medium text-gray-900 dark:text-gray-100">
                                    @svg('hugeicons-user', 'mr-2 h-5 w-5 text-gray-500 dark:text-gray-400')
                                    {{ __('User') }}
                                </h3>
                                <p class="mt-1 pl-7 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $selectedLog->user_name }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <h3 class="flex items-center text-lg font-medium text-gray-900 dark:text-gray-100">
                                    @svg('hugeicons-calendar-01', 'mr-2 h-5 w-5 text-gray-500 dark:text-gray-400')
                                    {{ __('Date and Time') }}
                                </h3>
                                <p class="mt-1 pl-7 text-sm text-gray-600 dark:text-gray-400">
                                    {{ Carbon\Carbon::parse($selectedLog->created_at)->timezone(auth()->user()->timezone)->locale(auth()->user()->language ?? app()->getLocale())->isoFormat('MMMM D, YYYY HH:mm:ss') }}
                                </p>
                            </div>
                            @if ($selectedLog->ip_address)
                                <div>
                                    <h3 class="flex items-center text-lg font-medium text-gray-900 dark:text-gray-100">
                                        @svg('hugeicons-globe', 'mr-2 h-5 w-5 text-gray-500 dark:text-gray-400')
                                        {{ __('IP Address') }}
                                    </h3>
                                    <p class="mt-1 pl-7 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $selectedLog->ip_address }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($selectedLog->context)
                        <div class="mt-6">
                            <h3 class="mb-3 flex items-center text-lg font-medium text-gray-900 dark:text-gray-100">
                                @svg('hugeicons-profile', 'mr-2 h-5 w-5 text-gray-500 dark:text-gray-400')
                                {{ __('Changes') }}
                            </h3>
                            <div class="overflow-x-auto rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                                            >
                                                {{ __('Field') }}
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                                            >
                                                {{ __('Change') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($selectedLog->context as $field => $change)
                                            <tr>
                                                <td
                                                    class="whitespace-nowrap px-3 py-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                                                >
                                                    {{ $field }}
                                                </td>
                                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">
                                                    @if (is_array($change))
                                                        <pre class="whitespace-pre-wrap">
{{ json_encode($change, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre
                                                        >
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
