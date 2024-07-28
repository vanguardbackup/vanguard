<div>
    @section('title', __('Statistics'))
    <x-slot name="header">
        {{ __('Statistics') }}
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (Auth::user()->backupTasks->count() === 0)
                <x-no-content withBackground>
                    <x-slot name="icon">
                        @svg('heroicon-o-chart-pie', 'h-16 w-16 text-primary-900 dark:text-white inline')
                    </x-slot>
                    <x-slot name="title">
                        {{ __('No Data Available') }}
                    </x-slot>
                    <x-slot name="description">
                        {{ __('Backup Tasks are required to be ran in order to generate statistical data.') }}
                    </x-slot>
                </x-no-content>
            @else
                <div x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 1000)" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden transition duration-300 ease-in-out hover:shadow-md">
                        <div class="px-6 py-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-full p-3 mr-4">
                                    @svg('heroicon-o-server', ['class' => 'h-6 w-6 text-primary-600 dark:text-primary-400'])
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Backup Data Statistics') }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Data backed up over different periods.') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                            <template x-if="!loaded">
                                <div class="space-y-2">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                </div>
                            </template>
                            <template x-if="loaded">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('Last 7 days') }}: <span class="font-semibold">{{ $dataBackedUpInThePastSevenDays }}</span></p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('Last month') }}: <span class="font-semibold">{{ $dataBackedUpInThePastMonth }}</span></p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Total') }}: <span class="font-semibold">{{ $dataBackedUpInTotal }}</span></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden transition duration-300 ease-in-out hover:shadow-md">
                        <div class="px-6 py-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-full p-3 mr-4">
                                    @svg('heroicon-o-link', ['class' => 'h-6 w-6 text-primary-600 dark:text-primary-400'])
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Linked Resources') }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Connected servers and destinations.') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                            <template x-if="!loaded">
                                <div class="space-y-2">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                </div>
                            </template>
                            <template x-if="loaded">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('Remote Servers') }}: <span class="font-semibold">{{ $linkedServers }}</span></p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Backup Destinations') }}: <span class="font-semibold">{{ $linkedBackupDestinations }}</span></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden transition duration-300 ease-in-out hover:shadow-md">
                        <div class="px-6 py-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-full p-3 mr-4">
                                    @svg('heroicon-o-clipboard-document-list', ['class' => 'h-6 w-6 text-primary-600 dark:text-primary-400'])
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Backup Tasks') }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Status of your backup tasks.') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                            <template x-if="!loaded">
                                <div class="space-y-2">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                </div>
                            </template>
                            <template x-if="loaded">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('Active') }}: <span class="font-semibold">{{ $activeBackupTasks }}</span></p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Paused') }}: <span class="font-semibold">{{ $pausedBackupTasks }}</span></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 1500)" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div x-data="{
                        chart: null,
                        init() {
                            this.createChart();
                            this.$watch('$store.darkMode', () => {
                                this.updateChart();
                            });
                        },
                        createChart() {
                            const ctx = document.getElementById('totalBackupTasksPast90Days').getContext('2d');
                            this.chart = new Chart(ctx, this.getChartConfig());
                        },
                        updateChart() {
                            if (this.chart) {
                                this.chart.destroy();
                            }
                            this.createChart();
                        },
                        getChartConfig() {
                            const isDarkMode = document.documentElement.classList.contains('dark');
                            const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)';

                            return {
                                type: 'bar',
                                data: {
                                    labels: {{ Js::from($backupDates) }},
                                    datasets: [
                                    {
                                    label: '{{ __("Files") }}',
                                    data: {{ Js::from($fileBackupCounts) }},
                                    backgroundColor: isDarkMode ? 'rgba(75, 85, 99, 0.8)' : 'rgba(250, 245, 255, 0.8)',
                                    borderColor: isDarkMode ? 'rgb(156, 163, 175)' : 'rgb(192, 180, 204)',
                                    borderWidth: 1
                                    },
                                    {
                                    label: '{{ __("Database") }}',
                                    data: {{ Js::from($databaseBackupCounts) }},
                                    backgroundColor: isDarkMode ? 'rgba(55, 65, 81, 0.8)' : 'rgba(237, 254, 255, 0.8)',
                                    borderColor: isDarkMode ? 'rgb(107, 114, 128)' : 'rgb(189, 220, 223)',
                                    borderWidth: 1
                                    }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'top',
                                            labels: {
                                                color: textColor
                                            }
                                        },
                                        title: {
                                            display: true,
                                            text: '{{ __("Backup Tasks - Past 90 Days") }}',
                                            color: textColor
                                        }
                                    },
                                    scales: {
                                        x: {
                                            stacked: true,
                                            ticks: { color: textColor }
                                        },
                                        y: {
                                            stacked: true,
                                            ticks: { color: textColor },
                                            beginAtZero: true
                                        }
                                    }
                                }
                            };
                        }
                    }" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden transition duration-300 ease-in-out hover:shadow-md">
                        <div class="px-6 py-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-full p-3 mr-4">
                                    @svg('heroicon-o-chart-bar', ['class' => 'h-6 w-6 text-primary-600 dark:text-primary-400'])
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Backups over the past 90 days') }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('All backups over the past ninety days.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-5">
                            <div x-show="!loaded" class="h-64 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            <div x-show="loaded" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="h-64">
                                <canvas id="totalBackupTasksPast90Days"></canvas>
                            </div>
                        </div>
                    </div>

                    <div x-data="{
                        chart: null,
                        init() {
                            this.createChart();
                            this.$watch('$store.darkMode', () => {
                                this.updateChart();
                            });
                        },
                        createChart() {
                            const ctx = document.getElementById('backupSuccessRateChart').getContext('2d');
                            this.chart = new Chart(ctx, this.getChartConfig());
                        },
                        updateChart() {
                            if (this.chart) {
                                this.chart.destroy();
                            }
                            this.createChart();
                        },
                        getChartConfig() {
                            const isDarkMode = document.documentElement.classList.contains('dark');
                            const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)';

                            return {
                                type: 'line',
                                data: {
                                    labels: {{ Js::from($backupSuccessRateLabels) }},
                                    datasets: [{
                                        label: '{{ __("Success Rate (%)") }}',
                                        data: {{ Js::from($backupSuccessRateData) }},
                                        borderColor: 'rgb(75, 192, 192)',
                                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                                        tension: 0.1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'top',
                                            labels: {
                                                color: textColor
                                            }
                                        },
                                        title: {
                                            display: true,
                                            text: '{{ __("Backup Success Rate - Last 6 Months") }}',
                                            color: textColor
                                        }
                                    },
                                    scales: {
                                        x: {
                                            ticks: { color: textColor }
                                        },
                                        y: {
                                            ticks: { color: textColor },
                                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            };
        }
    }" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden transition duration-300 ease-in-out hover:shadow-md">
                        <div class="px-6 py-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-full p-3 mr-4">
                                    @svg('heroicon-o-chart-bar', ['class' => 'h-6 w-6 text-primary-600 dark:text-primary-400'])
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Backup Success Rate') }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Success rate of backups over the last 6 months.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-5">
                            <div x-show="!loaded" class="h-64 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            <div x-show="loaded" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="h-64">
                                <canvas id="backupSuccessRateChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div x-data="{
        chart: null,
        init() {
            this.createChart();
            this.$watch('$store.darkMode', () => {
                this.updateChart();
            });
        },
        createChart() {
            const ctx = document.getElementById('averageBackupSizeChart').getContext('2d');
            this.chart = new Chart(ctx, this.getChartConfig());
        },
        updateChart() {
            if (this.chart) {
                this.chart.destroy();
            }
            this.createChart();
        },
        getChartConfig() {
            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)';

            return {
                type: 'bar',
                data: {
                    labels: {{ Js::from($averageBackupSizeLabels) }},
                    datasets: [{
                        label: '{{ __("Average Size (MB)") }}',
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
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: textColor
                            }
                        },
                        title: {
                            display: true,
                            text: '{{ __("Average Backup Size by Type") }}',
                            color: textColor
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: textColor }
                        },
                        y: {
                            ticks: { color: textColor },
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Size (MB)',
                                color: textColor
                            }
                        }
                    }
                }
            };
        }
    }" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden transition duration-300 ease-in-out hover:shadow-md">
                        <div class="px-6 py-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-full p-3 mr-4">
                                    @svg('heroicon-o-chart-bar', ['class' => 'h-6 w-6 text-primary-600 dark:text-primary-400'])
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Average Backup Size by Type') }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Average size of backups for each type.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-5">
                            <div x-show="!loaded" class="h-64 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            <div x-show="loaded" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="h-64">
                                <canvas id="averageBackupSizeChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div x-data="{
        chart: null,
        init() {
            this.createChart();
            this.$watch('$store.darkMode', () => {
                this.updateChart();
            });
        },
        createChart() {
            const ctx = document.getElementById('completionTimeChart').getContext('2d');
            this.chart = new Chart(ctx, this.getChartConfig());
        },
        updateChart() {
            if (this.chart) {
                this.chart.destroy();
            }
            this.createChart();
        },
        getChartConfig() {
            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)';

            return {
                type: 'line',
                data: {
                    labels: {{ Js::from($completionTimeLabels) }},
                    datasets: [{
                        label: '{{ __("Average Completion Time (minutes)") }}',
                        data: {{ Js::from($completionTimeData) }},
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: textColor
                            }
                        },
                        title: {
                            display: true,
                            text: '{{ __("Backup Task Completion Time Trend - Last 3 Months") }}',
                            color: textColor
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: textColor },
                            title: {
                                display: true,
                                text: 'Date',
                                color: textColor
                            }
                        },
                        y: {
                            ticks: { color: textColor },
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Time (minutes)',
                                color: textColor
                            }
                        }
                    }
                }
            };
        }
    }" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden transition duration-300 ease-in-out hover:shadow-md">
                        <div class="px-6 py-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-full p-3 mr-4">
                                    @svg('heroicon-o-chart-bar', ['class' => 'h-6 w-6 text-primary-600 dark:text-primary-400'])
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ __('Backup Task Completion Time Trend') }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Average completion time of backup tasks over the last 3 months.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-5">
                            <div x-show="!loaded" class="h-64 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            <div x-show="loaded" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="h-64">
                                <canvas id="completionTimeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
