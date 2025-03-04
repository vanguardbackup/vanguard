@if (Auth::user()->backupTasks->isNotEmpty())
    @section('title', __('Overview'))
@else
    @section('title', __('Steps to Get Started'))
@endif
<x-app-layout>
    @if (Auth::user()->backupTasks->isNotEmpty())
        <x-slot name="header">
            {{ __('Overview') }}
        </x-slot>

        <div class="mb-4">
            <div
                class="flex flex-col items-center rounded-[0.70rem] border bg-white p-4 transition duration-300 ease-in-out hover:shadow-md sm:flex-row sm:justify-between dark:border-gray-800/30 dark:bg-gray-800/50"
            >
                <div class="flex flex-col items-center sm:flex-row">
                    <div class="relative">
                        <div
                            class="h-12 w-12 overflow-hidden rounded-full border border-primary-300 shadow-sm dark:border-primary-600"
                        >
                            <img
                                class="h-full w-full object-cover"
                                src="{{ Auth::user()->gravatar('100') }}"
                                alt="{{ Auth::user()->first_name }}"
                            />
                        </div>
                    </div>
                    <div class="ml-3 mt-3 text-center sm:mt-0 sm:text-left">
                        <h3 class="text-lg font-semibold leading-tight text-gray-900 dark:text-gray-100">
                            {{ \App\Facades\Greeting::auto(Auth::user()->timezone) }}, {{ Auth::user()->first_name }}!
                        </h3>
                        <p class="mt-1 flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <span class="mr-1 flex h-1.5 w-1.5 rounded-full bg-primary-400 dark:bg-primary-500"></span>
                            {{ trans_choice(':count backup has|:count backups have', Auth::user()->backupTasklogCountToday(), ['count' => Auth::user()->backupTasklogCountToday()]) }}
                            {{ __('been completed today') }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex w-full justify-center space-x-2 sm:mt-0 sm:w-auto">
                    <a
                        href="{{ route('backup-tasks.create') }}"
                        class="group flex flex-col items-center rounded-lg p-2 transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-primary-600 transition-all duration-200 group-hover:bg-primary-200 dark:bg-primary-900/40 dark:text-primary-400 dark:group-hover:bg-primary-800/60"
                        >
                            @svg('hugeicons-plus-sign-circle', 'h-4 w-4')
                        </div>
                        <span class="mt-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Add Task') }}
                        </span>
                    </a>

                    <a
                        href="{{ route('backup-tasks.index') }}"
                        class="group flex flex-col items-center rounded-lg p-2 transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-purple-600 transition-all duration-200 group-hover:bg-purple-200 dark:bg-purple-900/40 dark:text-purple-400 dark:group-hover:bg-purple-800/60"
                        >
                            @svg('hugeicons-archive-02', 'h-4 w-4')
                        </div>
                        <span class="mt-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ __('View Tasks') }}
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Top row: Success Rate and Monthly Activity -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <x-chart-card
                title="{{ __('Backup Success Rate') }}"
                description="{{ __('Monthly backup success percentage') }}."
                icon="hugeicons-tick-01"
            >
                <div class="h-64">
                    <canvas id="backupSuccessRate"></canvas>
                </div>
            </x-chart-card>

            <x-chart-card
                title="{{ __('Monthly Activity') }}"
                description="{{ __('How many backups you ran each month') }}."
                icon="hugeicons-clock-01"
            >
                <div class="h-64">
                    <canvas id="totalBackupsPerMonth"></canvas>
                </div>
            </x-chart-card>
        </div>

        <!-- Second row: Backup Types and Backup Sizes -->
        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <x-chart-card
                title="{{ __('Backup Types') }}"
                description="{{ __('Breakdown of your file and database backups') }}."
                icon="hugeicons-file-02"
            >
                <div class="h-64">
                    <canvas id="backupTasksByType"></canvas>
                </div>
            </x-chart-card>

            <x-chart-card
                title="{{ __('Backup Sizes') }}"
                description="{{ __('Average size of each backup task') }}."
                icon="hugeicons-chart"
            >
                <div class="h-64">
                    <canvas id="backupSizeByType"></canvas>
                </div>
            </x-chart-card>
        </div>

        <div class="mt-6">
            @livewire('dashboard.upcoming-backup-tasks')
        </div>
        <script>
            document.addEventListener('livewire:navigated', function () {
                // Initialize charts
                const initializeCharts = function () {
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)'; // dark:text-gray-200 : text-gray-900
                    const backgroundColor = isDarkMode ? 'rgba(229, 231, 235, 0.24)' : 'rgba(17, 24, 39, 0.24)';

                    // Backup Success Rate Chart (moved to top position)
                    const ctx4 = document.getElementById('backupSuccessRate');

                    if (ctx4) {
                        const successData = {!! json_encode($successRateData, JSON_THROW_ON_ERROR) !!};

                        window.successRateChart = new Chart(ctx4.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: successData.labels,
                                datasets: [
                                    {
                                        label: '{!! __('Success Rate (%)') !!}',
                                        data: successData.data,
                                        borderColor: '#10b981', // Green color
                                        backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                        borderWidth: 2,
                                        tension: 0.2,
                                        fill: true,
                                        pointBackgroundColor: '#10b981',
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                    },
                                ],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                return `${context.dataset.label}: ${context.raw}%`;
                                            },
                                        },
                                    },
                                    legend: {
                                        display: false,
                                    },
                                },
                                scales: {
                                    x: {
                                        ticks: { color: textColor },
                                    },
                                    y: {
                                        min: 0,
                                        max: 100,
                                        ticks: {
                                            color: textColor,
                                            callback: function (value) {
                                                return value + '%';
                                            },
                                        },
                                    },
                                },
                            },
                        });
                    }

                    // Monthly Activity Chart
                    const label = '{!! __('Backup Tasks') !!}';
                    const ctx = document.getElementById('totalBackupsPerMonth');

                    if (ctx) {
                        window.monthlyChart = new Chart(ctx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: {!! $months !!},
                                datasets: [
                                    {
                                        label: label,
                                        data: {!! $counts !!},
                                        borderColor: textColor,
                                        backgroundColor: backgroundColor,
                                        tension: 0.2,
                                    },
                                ],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false,
                                    },
                                },
                                scales: {
                                    x: {
                                        ticks: { color: textColor },
                                    },
                                    y: {
                                        ticks: {
                                            color: textColor,
                                            stepSize: 1,
                                            precision: 0,
                                        },
                                        beginAtZero: true,
                                    },
                                },
                            },
                        });
                    }

                    // Type Distribution Chart
                    const type = '{!! __('Type') !!}';
                    const ctx2 = document.getElementById('backupTasksByType');

                    if (ctx2) {
                        const translations = {
                            Files: '{!! __('Files') !!}',
                            Database: '{!! __('Database') !!}',
                        };
                        const labels =
                            {!! json_encode(array_keys($backupTasksCountByType), JSON_THROW_ON_ERROR) !!}.map(
                                (label) => translations[label] || label,
                            ).map((label) => label.charAt(0).toUpperCase() + label.slice(1));

                        window.typeChart = new Chart(ctx2.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: type,
                                        data: {!! json_encode(array_values($backupTasksCountByType), JSON_THROW_ON_ERROR) !!},
                                        backgroundColor: isDarkMode
                                            ? ['rgba(147, 197, 253, 0.7)', 'rgba(167, 139, 250, 0.7)'] // Subdued blue and purple for dark mode
                                            : ['rgb(237,254,255)', 'rgb(250,245,255)'],
                                        borderColor: isDarkMode
                                            ? ['rgb(147, 197, 253)', 'rgb(167, 139, 250)'] // Brighter blue and purple borders for dark mode
                                            : ['rgb(189,220,223)', 'rgb(192,180,204)'],
                                        borderWidth: 0.8,
                                    },
                                ],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false,
                                    },
                                },
                                scales: {
                                    x: {
                                        ticks: { color: textColor },
                                    },
                                    y: {
                                        ticks: {
                                            color: textColor,
                                            stepSize: 1,
                                            precision: 0,
                                        },
                                        beginAtZero: true,
                                    },
                                },
                            },
                        });
                    }

                    // Backup Size Radar Chart
                    const ctx3 = document.getElementById('backupSizeByType');

                    if (ctx3) {
                        const sizeData = {!! json_encode($backupSizeData, JSON_THROW_ON_ERROR) !!};
                        const formattedSizes = sizeData.formatted;

                        window.sizeChart = new Chart(ctx3.getContext('2d'), {
                            type: 'radar',
                            data: {
                                labels: sizeData.labels,
                                datasets: sizeData.datasets.map((dataset) => ({
                                    ...dataset,
                                    borderColor: textColor,
                                    pointBackgroundColor: textColor,
                                })),
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    r: {
                                        angleLines: {
                                            color: backgroundColor,
                                        },
                                        grid: {
                                            color: backgroundColor,
                                        },
                                        pointLabels: {
                                            color: textColor,
                                            font: {
                                                size: 10,
                                            },
                                        },
                                        ticks: {
                                            color: textColor,
                                            backdropColor: isDarkMode ? 'rgb(17, 24, 39)' : 'rgb(255, 255, 255)',
                                            showLabelBackdrop: false,
                                            display: false,
                                        },
                                    },
                                },
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                return `${context.dataset.label}: ${formattedSizes[context.dataIndex]}`;
                                            },
                                        },
                                    },
                                    legend: {
                                        display: false,
                                    },
                                },
                            },
                        });
                    }
                };

                // Initialize charts on load
                initializeCharts();

                // Handle theme changes
                window.addEventListener('themeChanged', function (event) {
                    // Destroy existing charts if they exist
                    if (window.monthlyChart) {
                        window.monthlyChart.destroy();
                    }
                    if (window.typeChart) {
                        window.typeChart.destroy();
                    }
                    if (window.sizeChart) {
                        window.sizeChart.destroy();
                    }
                    if (window.successRateChart) {
                        window.successRateChart.destroy();
                    }

                    // Recreate charts with updated theme colors
                    initializeCharts();
                });
            });
        </script>
    @else
        <x-slot name="outsideContainer">
            @include('partials.steps-to-get-started.view')
        </x-slot>
    @endif
</x-app-layout>
