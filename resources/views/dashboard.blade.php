@if (Auth::user()->backupTasks->isNotEmpty())
    @section('title', 'Overview')
@else
    @section('title', 'Steps to Get Started')
@endif
<x-app-layout>
    @if (Auth::user()->backupTasks->isNotEmpty())
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Overview') }}
            </h2>
        </x-slot>
    <div class="pt-7 pb-12 mx-auto max-w-6xl">
        <div class="mb-4">
            <div class="flex">
                <div class="flex shrink-0">
                    <img class="h-14 w-14 rounded-full border border-gray-200 dark:border-gray-700" src="{{ Auth::user()->gravatar() }}" alt="{{ Auth::user()->name }}" />
                  <div>
                      <div class="font-semibold text-2xl ml-3 mt-0 dark:text-gray-100">
                          {{ \App\Facades\Greeting::auto(Auth::user()->timezone) }}, {{ Auth::user()->first_name }}!
                      </div>
                      <p class="text-gray-700 dark:text-gray-300 mt-0.5 ml-3">
                          {{ trans_choice(':count backup task has|:count backup tasks have', Auth::user()->backupTasklogCountToday(), ['count' => Auth::user()->backupTasklogCountToday()]) }} {{ __('been run today.') }}
                      </p>
                  </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-6">
                <div class="h-auto bg-white dark:bg-gray-800/50 dark:border-gray-800/30 rounded-[0.70rem] overflow-hidden border border-gray-950/5 p-5 my-3">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <div class="flex items-center">
                                @svg('heroicon-o-archive-box', 'h-7 w-7 text-gray-800 dark:text-gray-200 -mt-3
                                mr-2.5')
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                    {{ __('Backup Tasks Ran Per Month') }}
                                </h3>
                            </div>
                        </div>
                    </div>
                    <canvas id="totalBackupsPerMonth" width="auto"></canvas>
                </div>
            </div>
            <div class="col-span-6">
                <div class="h-auto bg-white dark:bg-gray-800/50 dark:border-gray-800/30 rounded-[0.70rem] overflow-hidden border border-gray-950/5 p-5 my-3">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <div class="flex items-center">
                                @svg('heroicon-o-swatch', 'h-7 w-7 text-gray-800 dark:text-gray-200 -mt-3 mr-2.5')
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                    {{ __('Backup Tasks by Type') }}
                                </h3>
                            </div>
                        </div>
                    </div>
                    <canvas id="backupTasksByType" width="auto"></canvas>
                </div>
            </div>
        </div>
        <div class="my-3">
            @livewire('dashboard.upcoming-backup-tasks')
        </div>
    </div>
        <script>
            document.addEventListener('livewire:navigated', function () {
                const ctx = document.getElementById('totalBackupsPerMonth').getContext('2d');
                const totalBackupsPerMonth = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: {!! $months !!},
                        datasets: [{
                            label: 'Total Backups',
                            data: {!! $counts !!},
                            borderColor: 'rgb(0,0,0)',
                            backgroundColor: 'rgba(0,0,0,0.24)',
                            tension: 0.2
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                    },
                });
            });
        </script>
        <script>
            document.addEventListener('livewire:navigated', function () {
                const ctx = document.getElementById('backupTasksByType').getContext('2d');
                const labels = {!! json_encode(array_keys($backupTasksCountByType), JSON_THROW_ON_ERROR) !!}.map(label => label.charAt(0).toUpperCase() + label.slice(1));
                const backupTasksByType = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Type',
                            data: {!! json_encode(array_values($backupTasksCountByType), JSON_THROW_ON_ERROR) !!},
                            backgroundColor: [
                                'rgb(237,254,255)',
                                'rgb(250,245,255)',
                            ],
                            borderColor: [
                                'rgb(189,220,223)',
                                'rgb(192,180,204)',
                            ],
                            borderWidth: 0.8
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                    },
                });
            });
        </script>
        @else
        @include('partials.steps-to-get-started.view')
    @endif
</x-app-layout>
