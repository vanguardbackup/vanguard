@section('title', __('Instance Details'))
<x-app-layout>
    <x-slot name="header">
        {{ __('Instance Details') }}
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 lg:flex-row">
                <!-- Left Column: Dashboard Details -->
                <div class="w-full space-y-6 lg:w-2/3">
                    <!-- System Information -->
                    <div
                        class="overflow-hidden rounded-lg border bg-white p-6 shadow sm:rounded-lg dark:border-gray-800 dark:bg-gray-800"
                    >
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('System Information') }}</h2>
                        <div class="mt-4 space-y-4">
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-php class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('PHP Version') }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">{{ $details['php_version'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-hard-drive class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Server Information') }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">{{ $details['server_info'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-computer class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Domain') }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">{{ $details['domain'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Laravel Components -->
                    <div
                        class="overflow-hidden rounded-lg border bg-white p-6 shadow sm:rounded-lg dark:border-gray-800 dark:bg-gray-800"
                    >
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Laravel Components') }}</h2>
                        <div class="mt-4 space-y-4">
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-browser class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Laravel Version') }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">{{ $details['laravel_version'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-permanent-job class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Laravel Horizon') }}
                                    </h3>
                                    <p
                                        class="{{ $details['horizon_running'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                                    >
                                        {{ $details['horizon_running'] ? __('Running') : __('Not Running') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-analytics-down class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Laravel Pulse') }}
                                    </h3>
                                    <p
                                        class="{{ $details['pulse_running'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                                    >
                                        {{ $details['pulse_running'] ? __('Running') : __('Not Running') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Management -->
                    <div
                        class="overflow-hidden rounded-lg border bg-white p-6 shadow sm:rounded-lg dark:border-gray-800 dark:bg-gray-800"
                    >
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('User Management') }}</h2>
                        <div class="mt-4 space-y-4">
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-crown class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Administrators') }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">{{ $details['admin_count'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-user-multiple-02 class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Total Users') }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">{{ $details['user_count'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Database and Backups -->
                    <div
                        class="overflow-hidden rounded-lg border bg-white p-6 shadow sm:rounded-lg dark:border-gray-800 dark:bg-gray-800"
                    >
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ __('Database and Backups') }}
                        </h2>
                        <div class="mt-4 space-y-4">
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-database class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Database Information') }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ __('Type') }}: {{ $details['database_type'] }}
                                    </p>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ __('Version') }}: {{ $details['database_version'] }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-archive-02 class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Total Backup Tasks') }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">{{ $details['backup_task_count'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Application Details -->
                    <div
                        class="overflow-hidden rounded-lg border bg-white p-6 shadow sm:rounded-lg dark:border-gray-800 dark:bg-gray-800"
                    >
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ __('Application Details') }}
                        </h2>
                        <div class="mt-4 space-y-4">
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-profile class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ config('app.name') }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ __('Version') }}: {{ $details['vanguard_version'] }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-mail-02 class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('SMTP Configuration') }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ __('Host') }}: {{ $details['smtp_config']['host'] }}
                                    </p>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ __('Port') }}: {{ $details['smtp_config']['port'] }}
                                    </p>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ __('Encryption') }}: {{ $details['smtp_config']['encryption'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Explanatory Information -->
                <div class="w-full space-y-6 lg:w-1/3">
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">
                            {{ __('About This Dashboard') }}
                        </h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            {{ __('This dashboard provides a comprehensive overview of your Vanguard instance. It displays crucial information about your system and application details.') }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">{{ __('Need Help?') }}</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            {{ __('If you need assistance understanding any part of this dashboard or have questions about your Vanguard instance, please don\'t hesitate to') }}
                            <a class="font-medium underline" href="{{ route('profile.help') }}">
                                {{ __('contact us') }}.
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
