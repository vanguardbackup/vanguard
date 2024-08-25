<div class="min-h-screen bg-gradient-to-b from-primary-950 to-primary-900 bg-cover text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold mb-4">{{ __('Steps to Get Started') }}</h2>
            <div class="w-20 h-1 bg-white mx-auto"></div>
            <p class="mt-4 text-lg text-gray-300">{{ __('Follow these steps to set up your backup system quickly and easily.') }}</p>
        </div>

        @if (!ssh_keys_exist())
            <x-notice
                type="warning"
                title="{{ __('SSH Keys Required') }}"
                text="{{ __('Please generate SSH keys before proceeding with the first step.') }}"
                class="mb-8"
                centered
            >
            </x-notice>
        @endif

        <div class="grid md:grid-cols-3 gap-8">
            @foreach ([
                [
                    'step' => 1,
                    'title' => __('Link your first Remote Server'),
                    'description' => __('Get started easily with popular Linux distributions like Ubuntu and Debian. We\'ve got you covered!'),
                    'icons' => ['ubuntu', 'debian', 'tux'],
                    'route' => 'remote-servers.create',
                    'buttonText' => __('Link Remote Server'),
                    'condition' => Auth::user()->remoteServers->isEmpty() && ssh_keys_exist()
                ],
                [
                    'step' => 2,
                    'title' => __('Connect a Backup Destination'),
                    'description' => __('Securely store your backups in the cloud. Easy setup with S3 buckets, and support for other popular storage solutions.'),
                    'icons' => ['aws', 'upcloud', 'gcp'],
                    'route' => 'backup-destinations.create',
                    'buttonText' => __('Add your Backup Destination'),
                    'condition' => Auth::user()->backupDestinations->isEmpty() && !Auth::user()->remoteServers->isEmpty()
                ],
                [
                    'step' => 3,
                    'title' => __('Make your first Backup Task'),
                    'description' => __('Effortlessly set up your Linux server backups to secure S3 buckets. Schedule for continuous data protection.'),
                    'additional_info' => __('Easily control when your backups run and be notified of their progress.'),
                    'route' => 'backup-tasks.create',
                    'buttonText' => __('Create your first Backup Task'),
                    'condition' => Auth::user()->remoteServers->isNotEmpty() && Auth::user()->backupDestinations->isNotEmpty()
                ]
            ] as $index => $step)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl border border-gray-200 dark:border-gray-700">
                    <div class="p-6 flex flex-col h-full relative">
                        <div class="absolute top-0 right-0 mt-4 mr-4 bg-primary-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg shadow-md">
                            {{ $index + 1 }}
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            {{ $step['title'] }}
                        </h3>
                        @if (isset($step['icons']))
                            <div class="flex justify-center space-x-6 my-6">
                                @foreach ($step['icons'] as $icon)
                                    <x-dynamic-component :component="'icons.' . $icon" class="h-14 w-14 {{ $icon === 'upcloud' ? 'text-gray-700 dark:text-white' : 'text-gray-700 dark:text-gray-300' }} transition-all duration-300 hover:scale-110" />
                                @endforeach
                            </div>
                        @endif
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 flex-grow">
                            {{ $step['description'] }}
                        </p>
                        @if (isset($step['additional_info']))
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                                {{ $step['additional_info'] }}
                            </p>
                        @endif
                        <div class="mt-auto">
                            @if (Auth::user()->{$step['step'] == 1 ? 'remoteServers' : 'backupDestinations'}->isNotEmpty())
                                <div class="bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200 p-3 rounded-lg text-sm">
                                    <p class="flex items-center font-medium">
                                        @svg('hugeicons-checkmark-circle-02', 'h-5 w-5 mr-2')
                                        <span>{{ __('You have completed this step.') }}</span>
                                    </p>
                                </div>
                            @elseif ($step['condition'])
                                <a href="{{ route($step['route']) }}" wire:navigate class="block w-full">
                                    <x-primary-button class="w-full justify-center bg-primary-600 hover:bg-primary-700 transition-colors duration-300">
                                        <span>{{ $step['buttonText'] }}</span>
                                        @svg('hugeicons-arrow-right-02', 'h-5 w-5 ml-2 inline-block transition-transform duration-300 group-hover:translate-x-1')
                                    </x-primary-button>
                                </a>
                            @else
                                <x-secondary-button class="w-full justify-center opacity-50 cursor-not-allowed" disabled>
                                    <span>{{ $step['buttonText'] }}</span>
                                    @svg('hugeicons-square-lock-02', 'h-5 w-5 ml-2 inline-block')
                                </x-secondary-button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-16">
            <x-application-logo class="h-24 w-24 mx-auto animate-pulse" />
            <p class="mt-4 text-lg text-gray-300">{{ __("You're on your way to secure, automated backups!") }}</p>
        </div>
    </div>
</div>
