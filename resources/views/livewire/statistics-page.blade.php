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
                <div x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 1000)"
                     class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">

                    <x-stat-card
                        icon="heroicon-o-server"
                        title="{{ __('Backup Data Statistics') }}"
                        description="{{ __('Data backed up over different periods.') }}"
                    >
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('Last 7 days') }}:
                                <span class="font-semibold">{{ $dataBackedUpInThePastSevenDays }}</span></p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('Last month') }}:
                                <span class="font-semibold">{{ $dataBackedUpInThePastMonth }}</span></p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Total') }}:
                                <span class="font-semibold">{{ $dataBackedUpInTotal }}</span></p>
                        </div>
                    </x-stat-card>

                    <x-stat-card
                        icon="heroicon-o-link"
                        title="{{ __('Linked Resources') }}"
                        description="{{ __('Connected servers and destinations.') }}"
                    >
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('Remote Servers') }}:
                                <span class="font-semibold">{{ $linkedServers }}</span></p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Backup Destinations') }}:
                                <span class="font-semibold">{{ $linkedBackupDestinations }}</span></p>
                        </div>
                    </x-stat-card>

                    <x-stat-card
                        icon="heroicon-o-clipboard-document-list"
                        title="{{ __('Backup Tasks') }}"
                        description="{{ __('Status of your backup tasks.') }}"
                    >
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('Active') }}:
                                <span class="font-semibold">{{ $activeBackupTasks }}</span></p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Paused') }}:
                                <span class="font-semibold">{{ $pausedBackupTasks }}</span></p>
                        </div>
                    </x-stat-card>

                </div>

                <div x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 1500)"
                     class="grid grid-cols-1 md:grid-cols-2 gap-8">


                    <x-chart-card
                        title="{{ __('Backups over the past 90 days') }}"
                        description="{{ __('All backups over the past ninety days.') }}"
                    >
                        <div
                            x-data="{
            chart: null,
            loaded: false,
            init() {
                this.createChart();
                this.$watch('$store.darkMode', () => {
                    this.updateChart();
                });
            },
            createChart() {
                const ctx = document.getElementById('totalBackupTasksPast90Days').getContext('2d');
                this.chart = new Chart(ctx, this.getChartConfig());
                this.loaded = true;
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
        }"
                            x-init="init()"
                        >
                            <div x-show="!loaded" class="h-64 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            <div x-show="loaded" x-transition:enter="transition-opacity duration-300"
                                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="h-64">
                                <canvas id="totalBackupTasksPast90Days"></canvas>
                            </div>
                        </div>
                    </x-chart-card>

                    <x-chart-card
                        title="{{ __('Backup Success Rate') }}"
                        description="{{ __('Success rate of backups over the last 6 months.') }}"
                    >
                        <div
                            x-data="{
            chart: null,
            loaded: false,
            init() {
                this.createChart();
                this.$watch('$store.darkMode', () => {
                    this.updateChart();
                });
            },
            createChart() {
                const ctx = document.getElementById('backupSuccessRateChart').getContext('2d');
                this.chart = new Chart(ctx, this.getChartConfig());
                this.loaded = true;
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
        }"
                            x-init="init()"
                        >
                            <div x-show="!loaded" class="h-64 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            <div x-show="loaded" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="h-64">
                                <canvas id="backupSuccessRateChart"></canvas>
                            </div>
                        </div>
                    </x-chart-card>

                    <x-chart-card
                        title="{{ __('Average Backup Size by Type') }}"
                        description="{{ __('Average size of backups for each type.') }}"
                    >
                        <div
                            x-data="{
            chart: null,
            loaded: false,
            init() {
                this.createChart();
                this.$watch('$store.darkMode', () => {
                    this.updateChart();
                });
            },
            createChart() {
                const ctx = document.getElementById('averageBackupSizeChart').getContext('2d');
                this.chart = new Chart(ctx, this.getChartConfig());
                this.loaded = true;
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
        }"
                            x-init="init()"
                        >
                            <div x-show="!loaded" class="h-64 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            <div x-show="loaded" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="h-64">
                                <canvas id="averageBackupSizeChart"></canvas>
                            </div>
                        </div>
                    </x-chart-card>

                    <x-chart-card
                        title="{{ __('Backup Task Completion Time Trend') }}"
                        description="{{ __('Average completion time of backup tasks over the last 3 months.') }}"
                    >
                        <div
                            x-data="{
            chart: null,
            loaded: false,
            init() {
                this.createChart();
                this.$watch('$store.darkMode', () => {
                    this.updateChart();
                });
            },
            createChart() {
                const ctx = document.getElementById('completionTimeChart').getContext('2d');
                this.chart = new Chart(ctx, this.getChartConfig());
                this.loaded = true;
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
        }"
                            x-init="init()"
                        >
                            <div x-show="!loaded" class="h-64 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            <div x-show="loaded" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="h-64">
                                <canvas id="completionTimeChart"></canvas>
                            </div>
                        </div>
                    </x-chart-card>

                    <x-chart-card
                        title="{{ __('API Usage Trend') }}"
                        description="{{ __('Daily API usage count over the last 30 days.') }}"
                    >
                        <div
                            x-data="{
    chart: null,
    loaded: false,
    init() {
        this.createChart();
        this.$watch('$store.darkMode', () => {
            this.updateChart();
        });
    },
    createChart() {
        const ctx = document.getElementById('apiUsageChart').getContext('2d');
        this.chart = new Chart(ctx, this.getChartConfig());
        this.loaded = true;
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
                labels: {{ Js::from($apiUsageLabels) }},
                datasets: [{
                    label: '{{ __("API Calls") }}',
                    data: {{ Js::from($apiUsageData) }},
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgb(75, 192, 192)',
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
                        text: '{{ __("API Usage Trend - Last 30 Days") }}',
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
                            text: 'Number of API Calls',
                            color: textColor
                        }
                    }
                }
            }
        };
    }
}"
                            x-init="init()"
                        >
                            <div x-show="!loaded" class="h-64 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            <div x-show="loaded" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="h-64">
                                <canvas id="apiUsageChart"></canvas>
                            </div>
                        </div>
                    </x-chart-card>

                    <x-chart-card
                        title="{{ __('API Usage by Method') }}"
                        description="{{ __('Daily API usage count by HTTP method over the last 30 days.') }}"
                    >
                        <div
                            x-data="{
    chart: null,
    loaded: false,
    init() {
        this.createChart();
        this.$watch('$store.darkMode', () => {
            this.updateChart();
        });
    },
    createChart() {
        const ctx = document.getElementById('apiUsageMethodChart').getContext('2d');
        this.chart = new Chart(ctx, this.getChartConfig());
        this.loaded = true;
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
            data: {{ Js::from($apiUsageMethodData) }},
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
                        text: '{{ __("API Usage by Method - Last 30 Days") }}',
                        color: textColor
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        ticks: { color: textColor },
                        title: {
                            display: true,
                            text: 'Date',
                            color: textColor
                        }
                    },
                    y: {
                        stacked: true,
                        ticks: { color: textColor },
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of API Calls',
                            color: textColor
                        }
                    }
                }
            }
        };
    }
}"
                            x-init="init()"
                        >
                            <div x-show="!loaded" class="h-64 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            <div x-show="loaded" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="h-64">
                                <canvas id="apiUsageMethodChart"></canvas>
                            </div>
                        </div>
                    </x-chart-card>
                </div>
            @endif
        </div>
    </div>
</div>
