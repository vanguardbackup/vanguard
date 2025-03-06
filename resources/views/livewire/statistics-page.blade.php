<div>
    @section('title', __('Statistics'))
    <x-slot name="header">
        {{ __('Statistics') }}
    </x-slot>
    <div class="pb-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (Auth::user()->backupTasks->count() === 0)
                <x-no-content withBackground>
                    <x-slot name="icon">
                        @svg('hugeicons-analytics-01', 'inline h-16 w-16 text-primary-900 dark:text-white')
                    </x-slot>
                    <x-slot name="title">
                        {{ __('No Data Available') }}
                    </x-slot>
                    <x-slot name="description">
                        {{ __('Backup Tasks are required to be ran in order to generate statistical data.') }}
                    </x-slot>
                </x-no-content>
            @else
                <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <x-stat-card
                        icon="hugeicons-cloud-server"
                        title="{{ __('Backup Volume Overview') }}"
                        description="{{ __('See how much data you have secured over time.') }}"
                        class="overflow-hidden bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-100 dark:border-gray-700"
                    >
                        <div class="space-y-4 mt-4">
                            <div class="relative">
                                <div class="flex justify-between items-baseline">
                                    <h4 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-medium">
                                        {{ __('Last 7 days') }}
                                    </h4>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ $dataBackedUpInThePastSevenDays }}
                                    </span>
                                </div>
                                <div class="w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full mt-2">
                                    <div class="h-1 bg-gray-900/80 dark:bg-white/80 rounded-full" style="width: 75%"></div>
                                </div>
                            </div>

                            <div class="relative">
                                <div class="flex justify-between items-baseline">
                                    <h4 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-medium">
                                        {{ __('Last month') }}
                                    </h4>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ $dataBackedUpInThePastMonth }}
                                    </span>
                                </div>
                                <div class="w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full mt-2">
                                    <div class="h-1 bg-gray-900/80 dark:bg-white/80 rounded-full opacity-90" style="width: 90%"></div>
                                </div>
                            </div>

                            <div class="relative">
                                <div class="flex justify-between items-baseline">
                                    <h4 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-medium">
                                        {{ __('Total') }}
                                    </h4>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ $dataBackedUpInTotal }}
                                    </span>
                                </div>
                                <div class="w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full mt-2">
                                    <div class="h-1 bg-blue-500/25 dark:bg-blue-400/25 rounded-full shadow-sm" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                    </x-stat-card>

                    <x-stat-card
                        icon="hugeicons-wireless-cloud-access"
                        title="{{ __('Connected Resources') }}"
                        description="{{ __('Connected servers and destinations to Vanguard.') }}"
                        class="overflow-hidden bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-100 dark:border-gray-700"
                    >
                        <div class="mt-4 space-y-5">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <h4 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-medium mb-1">
                                        {{ __('Remote Servers') }}
                                    </h4>
                                    <div class="flex items-baseline">
                                        <span class="text-2xl font-bold text-gray-900 dark:text-white mr-2">
                                            {{ $linkedServers }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('active connections') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <div class="flex-1">
                                    <h4 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-medium mb-1">
                                        {{ __('Backup Destinations') }}
                                    </h4>
                                    <div class="flex items-baseline">
                                        <span class="text-2xl font-bold text-gray-900 dark:text-white mr-2">
                                            {{ $linkedBackupDestinations }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('storage locations') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-stat-card>

                    <x-stat-card
                        icon="hugeicons-task-01"
                        title="{{ __('Backup Tasks') }}"
                        description="{{ __('Status of the backup tasks you have made.') }}"
                        class="overflow-hidden bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-100 dark:border-gray-700"
                    >
                        <div class="mt-4 space-y-5">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <h4 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-medium mb-1">
                                        {{ __('Active') }}
                                    </h4>
                                    <div class="flex items-baseline">
                                        <span class="text-2xl font-bold text-gray-900 dark:text-white mr-2">
                                            {{ $activeBackupTasks }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('running tasks') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <div class="flex-1">
                                    <h4 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-medium mb-1">
                                        {{ __('Paused') }}
                                    </h4>
                                    <div class="flex items-baseline">
                                        <span class="text-2xl font-bold text-gray-900 dark:text-white mr-2">
                                            {{ $pausedBackupTasks }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('suspended tasks') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-stat-card>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <x-chart-card
                        title="{{ __('Backups over the past 90 days') }}"
                        description="{{ __('All backups over the past ninety days.') }}"
                    >
                        <div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const createBackupChart = function() {
                                        const ctx = document
                                            .getElementById('totalBackupTasksPast90Days')
                                            .getContext('2d')

                                        const isDarkMode = document.documentElement.classList.contains('dark')
                                        const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)'

                                        const chart = new Chart(ctx, {
                                            type: 'bar',
                                            data: {
                                                labels: {{ Js::from($backupDates) }},
                                                datasets: [
                                                    {
                                                        label: '{{ __('Files') }}',
                                                        data: {{ Js::from($fileBackupCounts) }},
                                                        backgroundColor: isDarkMode
                                                            ? 'rgba(75, 85, 99, 0.8)'
                                                            : 'rgba(250, 245, 255, 0.8)',
                                                        borderColor: isDarkMode
                                                            ? 'rgb(156, 163, 175)'
                                                            : 'rgb(192, 180, 204)',
                                                        borderWidth: 1,
                                                    },
                                                    {
                                                        label: '{{ __('Database') }}',
                                                        data: {{ Js::from($databaseBackupCounts) }},
                                                        backgroundColor: isDarkMode
                                                            ? 'rgba(55, 65, 81, 0.8)'
                                                            : 'rgba(237, 254, 255, 0.8)',
                                                        borderColor: isDarkMode
                                                            ? 'rgb(107, 114, 128)'
                                                            : 'rgb(189, 220, 223)',
                                                        borderWidth: 1,
                                                    },
                                                ],
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: {
                                                        position: 'top',
                                                        labels: {
                                                            color: textColor,
                                                        },
                                                    },
                                                    title: {
                                                        display: true,
                                                        text: '{{ __('Backup Tasks - Past 90 Days') }}',
                                                        color: textColor,
                                                    },
                                                },
                                                scales: {
                                                    x: {
                                                        stacked: true,
                                                        ticks: { color: textColor },
                                                    },
                                                    y: {
                                                        stacked: true,
                                                        ticks: { color: textColor, stepSize: 1, precision: 0 },
                                                        beginAtZero: true,
                                                    },
                                                },
                                            },
                                        })

                                        // Handle dark mode toggle
                                        const darkModeToggle = document.querySelector('[x-on\\:click^="$store.darkMode.toggle"]')
                                        if (darkModeToggle) {
                                            darkModeToggle.addEventListener('click', function() {
                                                setTimeout(function() {
                                                    chart.destroy()
                                                    createBackupChart()
                                                }, 100)
                                            })
                                        }
                                    }

                                    createBackupChart()
                                })
                            </script>
                            <div class="h-64">
                                <canvas id="totalBackupTasksPast90Days"></canvas>
                            </div>
                        </div>
                    </x-chart-card>

                    <x-chart-card
                        title="{{ __('Backup Success Rate') }}"
                        description="{{ __('Success rate of backups over the last 6 months.') }}"
                    >
                        <div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const createSuccessRateChart = function() {
                                        const ctx = document
                                            .getElementById('backupSuccessRateChart')
                                            .getContext('2d')

                                        const isDarkMode = document.documentElement.classList.contains('dark')
                                        const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)'

                                        const chart = new Chart(ctx, {
                                            type: 'line',
                                            data: {
                                                labels: {{ Js::from($backupSuccessRateLabels) }},
                                                datasets: [
                                                    {
                                                        label: '{{ __('Success Rate (%)') }}',
                                                        data: {{ Js::from($backupSuccessRateData) }},
                                                        borderColor: 'rgb(75, 192, 192)',
                                                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                                                        tension: 0.1,
                                                    },
                                                ],
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: {
                                                        position: 'top',
                                                        labels: {
                                                            color: textColor,
                                                        },
                                                    },
                                                    title: {
                                                        display: true,
                                                        text: '{{ __('Backup Success Rate - Last 6 Months') }}',
                                                        color: textColor,
                                                    },
                                                },
                                                scales: {
                                                    x: {
                                                        ticks: { color: textColor },
                                                    },
                                                    y: {
                                                        ticks: { color: textColor },
                                                        beginAtZero: true,
                                                        max: 100,
                                                    },
                                                },
                                            },
                                        })

                                        // Handle dark mode toggle
                                        const darkModeToggle = document.querySelector('[x-on\\:click^="$store.darkMode.toggle"]')
                                        if (darkModeToggle) {
                                            darkModeToggle.addEventListener('click', function() {
                                                setTimeout(function() {
                                                    chart.destroy()
                                                    createSuccessRateChart()
                                                }, 100)
                                            })
                                        }
                                    }

                                    createSuccessRateChart()
                                })
                            </script>
                            <div class="h-64">
                                <canvas id="backupSuccessRateChart"></canvas>
                            </div>
                        </div>
                    </x-chart-card>

                    <x-chart-card
                        title="{{ __('Average Backup Size by Type') }}"
                        description="{{ __('Average size of backups for each type.') }}"
                    >
                        <div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const createSizeChart = function() {
                                        const ctx = document
                                            .getElementById('averageBackupSizeChart')
                                            .getContext('2d')

                                        const isDarkMode = document.documentElement.classList.contains('dark')
                                        const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)'

                                        const chart = new Chart(ctx, {
                                            type: 'bar',
                                            data: {
                                                labels: {{ Js::from($averageBackupSizeLabels) }},
                                                datasets: [
                                                    {
                                                        label: '{{ __('Average Size (MB)') }}',
                                                        data: {{ Js::from($averageBackupSizeData) }},
                                                        backgroundColor: [
                                                            'rgba(255, 99, 132, 0.8)',
                                                            'rgba(54, 162, 235, 0.8)',
                                                            'rgba(255, 206, 86, 0.8)',
                                                            'rgba(75, 192, 192, 0.8)',
                                                            'rgba(153, 102, 255, 0.8)',
                                                        ],
                                                        borderColor: [
                                                            'rgb(255, 99, 132)',
                                                            'rgb(54, 162, 235)',
                                                            'rgb(255, 206, 86)',
                                                            'rgb(75, 192, 192)',
                                                            'rgb(153, 102, 255)',
                                                        ],
                                                        borderWidth: 1,
                                                    },
                                                ],
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: {
                                                        position: 'top',
                                                        labels: {
                                                            color: textColor,
                                                        },
                                                    },
                                                    title: {
                                                        display: true,
                                                        text: '{{ __('Average Backup Size by Type') }}',
                                                        color: textColor,
                                                    },
                                                },
                                                scales: {
                                                    x: {
                                                        ticks: { color: textColor },
                                                    },
                                                    y: {
                                                        ticks: { color: textColor },
                                                        beginAtZero: true,
                                                        title: {
                                                            display: true,
                                                            text: 'Size (MB)',
                                                            color: textColor,
                                                        },
                                                    },
                                                },
                                            },
                                        })

                                        // Handle dark mode toggle
                                        const darkModeToggle = document.querySelector('[x-on\\:click^="$store.darkMode.toggle"]')
                                        if (darkModeToggle) {
                                            darkModeToggle.addEventListener('click', function() {
                                                setTimeout(function() {
                                                    chart.destroy()
                                                    createSizeChart()
                                                }, 100)
                                            })
                                        }
                                    }

                                    createSizeChart()
                                })
                            </script>
                            <div class="h-64">
                                <canvas id="averageBackupSizeChart"></canvas>
                            </div>
                        </div>
                    </x-chart-card>

                    <x-chart-card
                        title="{{ __('Backup Task Completion Time Trend') }}"
                        description="{{ __('Average completion time of backup tasks over the last 3 months.') }}"
                    >
                        <div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const createCompletionTimeChart = function() {
                                        const ctx = document
                                            .getElementById('completionTimeChart')
                                            .getContext('2d')

                                        const isDarkMode = document.documentElement.classList.contains('dark')
                                        const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)'

                                        const chart = new Chart(ctx, {
                                            type: 'line',
                                            data: {
                                                labels: {{ Js::from($completionTimeLabels) }},
                                                datasets: [
                                                    {
                                                        label: '{{ __('Average Completion Time (minutes)') }}',
                                                        data: {{ Js::from($completionTimeData) }},
                                                        borderColor: 'rgb(75, 192, 192)',
                                                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                                                        tension: 0.1,
                                                    },
                                                ],
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: {
                                                        position: 'top',
                                                        labels: {
                                                            color: textColor,
                                                        },
                                                    },
                                                    title: {
                                                        display: true,
                                                        text: '{{ __('Backup Task Completion Time Trend - Last 3 Months') }}',
                                                        color: textColor,
                                                    },
                                                },
                                                scales: {
                                                    x: {
                                                        ticks: { color: textColor },
                                                        title: {
                                                            display: true,
                                                            text: 'Date',
                                                            color: textColor,
                                                        },
                                                    },
                                                    y: {
                                                        ticks: { color: textColor },
                                                        beginAtZero: true,
                                                        title: {
                                                            display: true,
                                                            text: 'Time (minutes)',
                                                            color: textColor,
                                                        },
                                                    },
                                                },
                                            },
                                        })

                                        // Handle dark mode toggle
                                        const darkModeToggle = document.querySelector('[x-on\\:click^="$store.darkMode.toggle"]')
                                        if (darkModeToggle) {
                                            darkModeToggle.addEventListener('click', function() {
                                                setTimeout(function() {
                                                    chart.destroy()
                                                    createCompletionTimeChart()
                                                }, 100)
                                            })
                                        }
                                    }

                                    createCompletionTimeChart()
                                })
                            </script>
                            <div class="h-64">
                                <canvas id="completionTimeChart"></canvas>
                            </div>
                        </div>
                    </x-chart-card>

                    <x-chart-card
                        title="{{ __('API Usage Trend') }}"
                        description="{{ __('Daily API usage count over the last 30 days.') }}"
                    >
                        <div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const createApiUsageChart = function() {
                                        const ctx = document
                                            .getElementById('apiUsageChart')
                                            .getContext('2d')

                                        const isDarkMode = document.documentElement.classList.contains('dark')
                                        const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)'

                                        const chart = new Chart(ctx, {
                                            type: 'bar',
                                            data: {
                                                labels: {{ Js::from($apiUsageLabels) }},
                                                datasets: [
                                                    {
                                                        label: '{{ __('API Calls') }}',
                                                        data: {{ Js::from($apiUsageData) }},
                                                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                                                        borderColor: 'rgb(75, 192, 192)',
                                                        borderWidth: 1,
                                                    },
                                                ],
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: {
                                                        position: 'top',
                                                        labels: {
                                                            color: textColor,
                                                        },
                                                    },
                                                    title: {
                                                        display: true,
                                                        text: '{{ __('API Usage Trend - Last 30 Days') }}',
                                                        color: textColor,
                                                    },
                                                },
                                                scales: {
                                                    x: {
                                                        ticks: { color: textColor },
                                                        title: {
                                                            display: true,
                                                            text: 'Date',
                                                            color: textColor,
                                                        },
                                                    },
                                                    y: {
                                                        ticks: { color: textColor },
                                                        beginAtZero: true,
                                                        title: {
                                                            display: true,
                                                            text: 'Number of API Calls',
                                                            color: textColor,
                                                        },
                                                    },
                                                },
                                            },
                                        })

                                        // Handle dark mode toggle
                                        const darkModeToggle = document.querySelector('[x-on\\:click^="$store.darkMode.toggle"]')
                                        if (darkModeToggle) {
                                            darkModeToggle.addEventListener('click', function() {
                                                setTimeout(function() {
                                                    chart.destroy()
                                                    createApiUsageChart()
                                                }, 100)
                                            })
                                        }
                                    }

                                    createApiUsageChart()
                                })
                            </script>
                            <div class="h-64">
                                <canvas id="apiUsageChart"></canvas>
                            </div>
                        </div>
                    </x-chart-card>

                    <x-chart-card
                        title="{{ __('API Usage by Method') }}"
                        description="{{ __('Daily API usage count by HTTP method over the last 30 days.') }}"
                    >
                        <div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const createApiMethodChart = function() {
                                        const ctx = document
                                            .getElementById('apiUsageMethodChart')
                                            .getContext('2d')

                                        const isDarkMode = document.documentElement.classList.contains('dark')
                                        const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)'

                                        const chart = new Chart(ctx, {
                                            type: 'bar',
                                            data: {{ Js::from($apiUsageMethodData) }},
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: {
                                                        position: 'top',
                                                        labels: {
                                                            color: textColor,
                                                        },
                                                    },
                                                    title: {
                                                        display: true,
                                                        text: '{{ __('API Usage by Method - Last 30 Days') }}',
                                                        color: textColor,
                                                    },
                                                },
                                                scales: {
                                                    x: {
                                                        stacked: true,
                                                        ticks: { color: textColor },
                                                        title: {
                                                            display: true,
                                                            text: 'Date',
                                                            color: textColor,
                                                        },
                                                    },
                                                    y: {
                                                        stacked: true,
                                                        ticks: { color: textColor },
                                                        beginAtZero: true,
                                                        title: {
                                                            display: true,
                                                            text: 'Number of API Calls',
                                                            color: textColor,
                                                        },
                                                    },
                                                },
                                            },
                                        })

                                        // Handle dark mode toggle
                                        const darkModeToggle = document.querySelector('[x-on\\:click^="$store.darkMode.toggle"]')
                                        if (darkModeToggle) {
                                            darkModeToggle.addEventListener('click', function() {
                                                setTimeout(function() {
                                                    chart.destroy()
                                                    createApiMethodChart()
                                                }, 100)
                                            })
                                        }
                                    }

                                    createApiMethodChart()
                                })
                            </script>
                            <div class="h-64">
                                <canvas id="apiUsageMethodChart"></canvas>
                            </div>
                        </div>
                    </x-chart-card>
                </div>
            @endif
        </div>
    </div>
</div>
