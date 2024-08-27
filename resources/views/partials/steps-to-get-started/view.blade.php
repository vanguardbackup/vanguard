<div class="min-h-screen bg-gradient-to-b from-primary-950 to-primary-900 bg-cover py-16 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-12 text-center">
            <h2 class="mb-4 text-4xl font-bold">
                {{ __('Steps to Get Started') }}
            </h2>
            <div class="mx-auto h-1 w-20 bg-white"></div>
            <p class="mt-4 text-lg text-gray-300">
                {{ __('Follow these steps to set up your backup system quickly and easily.') }}
            </p>
        </div>

        @if (! ssh_keys_exist())
            <x-notice
                type="warning"
                title="{{ __('SSH Keys Required') }}"
                text="{{ __('Please generate SSH keys before proceeding with the first step.') }}"
                class="mb-8"
                centered
            ></x-notice>
        @endif

        <div class="grid gap-8 md:grid-cols-3">
            @foreach ([
                    [
                        'step' => 1,
                        'title' => __('Link your first Remote Server'),
                        'description' => __(
                            'Get started easily with popular Linux distributions like Ubuntu and Debian. We\'ve got you covered!'
                        ),
                        'icons' => ['ubuntu', 'debian', 'tux'],
                        'route' => 'remote-servers.create',
                        'buttonText' => __('Link Remote Server'),
                        'condition' => Auth::user()->remoteServers->isEmpty() && ssh_keys_exist()
                    ],
                    [
                        'step' => 2,
                        'title' => __('Connect a Backup Destination'),
                        'description' => __(
                            'Securely store your backups in the cloud. Easy setup with S3 buckets, and support for other popular storage solutions.'
                        ),
                        'icons' => ['aws', 'upcloud', 'gcp'],
                        'route' => 'backup-destinations.create',
                        'buttonText' => __('Add your Backup Destination'),
                        'condition' => Auth::user()->backupDestinations->isEmpty() && ! Auth::user()->remoteServers->isEmpty()
                    ],
                    [
                        'step' => 3,
                        'title' => __('Make your first Backup Task'),
                        'description' => __(
                            'Effortlessly set up your Linux server backups to secure S3 buckets. Schedule for continuous data protection.'
                        ),
                        'additional_info' => __('Easily control when your backups run and be notified of their progress.'),
                        'route' => 'backup-tasks.create',
                        'buttonText' => __('Create your first Backup Task'),
                        'condition' => Auth::user()->remoteServers->isNotEmpty() && Auth::user()->backupDestinations->isNotEmpty()
                    ]
                ]
                as $index => $step)
                <div
                    class="transform overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg transition-all duration-300 hover:shadow-2xl dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="relative flex h-full flex-col p-6">
                        <div
                            class="absolute right-0 top-0 mr-4 mt-4 flex h-10 w-10 items-center justify-center rounded-full bg-primary-500 text-lg font-bold text-white shadow-md"
                        >
                            {{ $index + 1 }}
                        </div>
                        <h3 class="mb-4 text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $step['title'] }}
                        </h3>
                        @if (isset($step['icons']))
                            <div class="my-6 flex justify-center space-x-6">
                                @foreach ($step['icons'] as $icon)
                                    <x-dynamic-component
                                        :component="'icons.' . $icon"
                                        class="h-14 w-14 {{ $icon === 'upcloud' ? 'text-gray-700 dark:text-white' : 'text-gray-700 dark:text-gray-300' }} transition-all duration-300 hover:scale-110"
                                    />
                                @endforeach
                            </div>
                        @endif

                        <p class="mb-4 flex-grow text-sm text-gray-600 dark:text-gray-400">
                            {{ $step['description'] }}
                        </p>
                        @if (isset($step['additional_info']))
                            <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                                {{ $step['additional_info'] }}
                            </p>
                        @endif

                        <div class="mt-auto">
                            @if (Auth::user()->{$step['step'] == 1 ? 'remoteServers' : 'backupDestinations'}->isNotEmpty())
                                <div
                                    class="rounded-lg bg-green-100 p-3 text-sm text-green-700 dark:bg-green-800 dark:text-green-200"
                                >
                                    <p class="flex items-center font-medium">
                                        @svg('hugeicons-checkmark-circle-02', 'mr-2 h-5 w-5')
                                        <span>
                                            {{ __('You have completed this step.') }}
                                        </span>
                                    </p>
                                </div>
                            @elseif ($step['condition'])
                                <a href="{{ route($step['route']) }}" wire:navigate class="block w-full">
                                    <x-primary-button
                                        class="w-full justify-center bg-primary-600 transition-colors duration-300 hover:bg-primary-700"
                                    >
                                        <span>{{ $step['buttonText'] }}</span>
                                        @svg('hugeicons-arrow-right-02', 'ml-2 inline-block h-5 w-5 transition-transform duration-300 group-hover:translate-x-1')
                                    </x-primary-button>
                                </a>
                            @else
                                <x-secondary-button
                                    class="w-full cursor-not-allowed justify-center opacity-50"
                                    disabled
                                >
                                    <span>{{ $step['buttonText'] }}</span>
                                    @svg('hugeicons-square-lock-02', 'ml-2 inline-block h-5 w-5')
                                </x-secondary-button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-16 text-center">
            <x-application-logo class="mx-auto h-24 w-24 animate-pulse" />
            <p class="mt-4 text-lg text-gray-300">
                {{ __("You're on your way to secure, automated backups!") }}
            </p>
        </div>
    </div>
</div>
