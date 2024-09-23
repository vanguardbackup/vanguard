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
                    <div class="rounded-lg bg-white p-6" x-data="{ expanded: false }">
                        <button @click="expanded = !expanded" class="flex w-full items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">System Information</h2>
                            <x-hugeicons-arrow-down-01
                                x-bind:class="expanded ? 'rotate-180 transform' : ''"
                                class="h-5 w-5 text-gray-500"
                            />
                        </button>
                        <div x-show="expanded" x-collapse class="mt-4 space-y-4">
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-hard-drive class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">PHP Version</h3>
                                    <p class="text-gray-600">{{ $details['php_version'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-search-01 class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Server Information</h3>
                                    <p class="text-gray-600">{{ $details['server_info'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-earth class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Domain</h3>
                                    <p class="text-gray-600">{{ $details['domain'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Laravel Components -->
                    <div class="rounded-lg bg-white p-6" x-data="{ expanded: false }">
                        <button @click="expanded = !expanded" class="flex w-full items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">Laravel Components</h2>
                            <x-hugeicons-arrow-down-01
                                x-bind:class="expanded ? 'rotate-180 transform' : ''"
                                class="h-5 w-5 text-gray-500"
                            />
                        </button>
                        <div x-show="expanded" x-collapse class="mt-4 space-y-4">
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-browser class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Laravel Version</h3>
                                    <p class="text-gray-600">{{ $details['laravel_version'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-new-job class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Laravel Horizon</h3>
                                    <p class="{{ $details['horizon_running'] ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $details['horizon_running'] ? 'Running' : 'Not Running' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-waterfall-up-02 class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Laravel Pulse</h3>
                                    <p class="{{ $details['pulse_running'] ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $details['pulse_running'] ? 'Running' : 'Not Running' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Management -->
                    <div class="rounded-lg bg-white p-6" x-data="{ expanded: false }">
                        <button @click="expanded = !expanded" class="flex w-full items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">User Management</h2>
                            <x-hugeicons-arrow-down-01
                                x-bind:class="expanded ? 'rotate-180 transform' : ''"
                                class="h-5 w-5 text-gray-500"
                            />
                        </button>
                        <div x-show="expanded" x-collapse class="mt-4 space-y-4">
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-crown class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Administrators</h3>
                                    <p class="text-gray-600">{{ $details['admin_count'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-user-multiple-02 class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Total Users</h3>
                                    <p class="text-gray-600">{{ $details['user_count'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Database and Backups -->
                    <div class="rounded-lg bg-white p-6" x-data="{ expanded: false }">
                        <button @click="expanded = !expanded" class="flex w-full items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">Database and Backups</h2>
                            <x-hugeicons-arrow-down-01
                                x-bind:class="expanded ? 'rotate-180 transform' : ''"
                                class="h-5 w-5 text-gray-500"
                            />
                        </button>
                        <div x-show="expanded" x-collapse class="mt-4 space-y-4">
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-database class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Database Information</h3>
                                    <p class="text-gray-600">Type: {{ $details['database_type'] }}</p>
                                    <p class="text-gray-600">Version: {{ $details['database_version'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-archive-02 class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Total Backup Tasks</h3>
                                    <p class="text-gray-600">{{ $details['backup_task_count'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Application Details -->
                    <div class="rounded-lg bg-white p-6" x-data="{ expanded: false }">
                        <button @click="expanded = !expanded" class="flex w-full items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">Application Details</h2>
                            <x-hugeicons-arrow-down-01
                                x-bind:class="expanded ? 'rotate-180 transform' : ''"
                                class="h-5 w-5 text-gray-500"
                            />
                        </button>
                        <div x-show="expanded" x-collapse class="mt-4 space-y-4">
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-profile class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Vanguard</h3>
                                    <p class="text-gray-600">Version: {{ $details['vanguard_version'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <x-hugeicons-mail-02 class="h-6 w-6 flex-shrink-0 text-gray-500" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">SMTP Configuration</h3>
                                    <p class="text-gray-600">Host: {{ $details['smtp_config']['host'] }}</p>
                                    <p class="text-gray-600">Port: {{ $details['smtp_config']['port'] }}</p>
                                    <p class="text-gray-600">
                                        Encryption: {{ $details['smtp_config']['encryption'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Explanatory Information -->
                <div class="w-full space-y-6 lg:w-1/3">
                    <div class="rounded-lg bg-white p-6">
                        <h2 class="mb-4 text-xl font-bold text-gray-900">About This Dashboard</h2>
                        <p class="text-gray-600">
                            This dashboard provides a comprehensive overview of your Vanguard instance. It displays
                            crucial information about your system, Laravel components, user management, database, and
                            application details.
                        </p>
                    </div>
                    <div class="rounded-lg bg-white p-6">
                        <h2 class="mb-4 text-xl font-bold text-gray-900">Why It Matters</h2>
                        <ul class="list-inside list-disc space-y-2 text-gray-600">
                            <li>System Information helps you understand your server environment.</li>
                            <li>Laravel Components show the status of key Laravel features.</li>
                            <li>User Management gives an overview of your user base.</li>
                            <li>Database and Backups information ensures your data is secure.</li>
                            <li>Application Details provide insights into your Vanguard setup.</li>
                        </ul>
                    </div>
                    <div class="rounded-lg bg-white p-6">
                        <h2 class="mb-4 text-xl font-bold text-gray-900">Need Help?</h2>
                        <p class="text-gray-600">
                            If you need assistance understanding any part of this dashboard or have questions about your
                            Vanguard instance, please don't hesitate to
                            <a class="font-medium underline" href="{{ route('profile.help') }}">contact us.</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
