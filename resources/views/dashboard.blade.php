@if (Auth::user()->backupTasks->isNotEmpty())
    @section('title', __('Overview'))
@else
    @section('title', __('Steps to Get Started'))
@endif
<x-app-layout>
    @if (Auth::user()->backupTasks->isNotEmpty())
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Overview') }}
            </h2>
        </x-slot>
        <div class="pt-4 pb-8 px-4 mx-auto max-w-full sm:max-w-6xl">
            <div class="mb-4">
                <div class="flex flex-col sm:flex-row items-center">
                    <div class="flex shrink-0">
                        <img class="h-14 w-14 rounded-full border border-gray-200 dark:border-gray-700" src="{{ Auth::user()->gravatar() }}" alt="{{ Auth::user()->name }}" />
                        <div class="ml-3 mt-3 sm:mt-0">
                            <div class="font-semibold text-2xl dark:text-gray-100">
                                {{ \App\Facades\Greeting::auto(Auth::user()->timezone) }}, {{ Auth::user()->first_name }}!
                            </div>
                            <p class="text-gray-700 dark:text-gray-300 mt-0.5">
                                {{ trans_choice(':count backup task has|:count backup tasks have', Auth::user()->backupTasklogCountToday(), ['count' => Auth::user()->backupTasklogCountToday()]) }} {{ __('been run today.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="h-auto bg-white dark:bg-gray-800/50 dark:border-gray-800/30 rounded-lg overflow-hidden border border-gray-950/5 p-5">
                    <div class="flex items-center mb-4">
                        @svg('heroicon-o-clock', 'h-7 w-7 text-gray-800 dark:text-gray-200 mr-2.5')
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ __('Monthly Backup Task Activity') }}
                        </h3>
                    </div>
                    <canvas id="totalBackupsPerMonth" width="auto"></canvas>
                </div>
                <div class="h-auto bg-white dark:bg-gray-800/50 dark:border-gray-800/30 rounded-lg overflow-hidden border border-gray-950/5 p-5">
                    <div class="flex items-center mb-4">
                        @svg('heroicon-o-swatch', 'h-7 w-7 text-gray-800 dark:text-gray-200 mr-2.5')
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ __('Backup Tasks Categorized by Type') }}
                        </h3>
                    </div>
                    <canvas id="backupTasksByType" width="auto"></canvas>
                </div>
            </div>
            <div class="my-3">
                @livewire('dashboard.upcoming-backup-tasks')
            </div>
        </div>
        <script>
            document.addEventListener('livewire:navigated', function () {
                function createCharts() {
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    const textColor = isDarkMode ? 'rgb(229, 231, 235)' : 'rgb(17, 24, 39)'; // dark:text-gray-200 : text-gray-900
                    const backgroundColor = isDarkMode ? 'rgba(229, 231, 235, 0.24)' : 'rgba(17, 24, 39, 0.24)';

                    const label = '{!! __('Backup Tasks') !!}';
                    const ctx = document.getElementById('totalBackupsPerMonth').getContext('2d');
                    const totalBackupsPerMonth = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: {!! $months !!},
                            datasets: [{
                                label: label,
                                data: {!! $counts !!},
                                borderColor: textColor,
                                backgroundColor: backgroundColor,
                                tension: 0.2
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    ticks: { color: textColor }
                                },
                                y: {
                                    ticks: { color: textColor }
                                }
                            }
                        },
                    });

                    const type = '{!! __('Type') !!}';
                    const ctx2 = document.getElementById('backupTasksByType').getContext('2d');
                    const translations = {
                        'Files': '{!! __('Files') !!}',
                        'Database': '{!! __('Database') !!}'
                    };
                    const labels = {!! json_encode(array_keys($backupTasksCountByType), JSON_THROW_ON_ERROR) !!}
                        .map(label => translations[label] || label)
                        .map(label => label.charAt(0).toUpperCase() + label.slice(1));
                    const backupTasksByType = new Chart(ctx2, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: type,
                                data: {!! json_encode(array_values($backupTasksCountByType), JSON_THROW_ON_ERROR) !!},
                                backgroundColor: isDarkMode
                                    ? ['rgb(55, 65, 81)', 'rgb(75, 85, 99)']  // dark:bg-gray-700, dark:bg-gray-600
                                    : ['rgb(237,254,255)', 'rgb(250,245,255)'],
                                borderColor: isDarkMode
                                    ? ['rgb(107, 114, 128)', 'rgb(156, 163, 175)']  // dark:border-gray-500, dark:border-gray-400
                                    : ['rgb(189,220,223)', 'rgb(192,180,204)'],
                                borderWidth: 0.8
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    ticks: { color: textColor }
                                },
                                y: {
                                    ticks: { color: textColor }
                                }
                            }
                        },
                    });

                    return { totalBackupsPerMonth, backupTasksByType };
                }

                let charts = createCharts();

                window.addEventListener('themeChanged', function(event) {
                    charts.totalBackupsPerMonth.destroy();
                    charts.backupTasksByType.destroy();
                    charts = createCharts();
                });
            });
        </script>

    @else
        @include('partials.steps-to-get-started.view')
    @endif
</x-app-layout>
