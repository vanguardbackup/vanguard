<div class="h-full md:h-screen bg-primary-950 bg-cover text-white">
    <div class="max-w-7xl mx-auto">
        <div class="container mx-auto px-4 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-1">
                    <h2 class="text-4xl font-bold mb-8">{{ __('Steps to Get Started') }}</h2>
                    <hr class="border-2 border-white w-20 mb-12"/>
                </div>
            </div>
            <div class="grid md:grid-cols-12 gap-6 md:space-x-3">
                <div class="bg-white rounded-[0.70rem] px-5 py-5 text-gray-950 col-span-4">
                    <h1 class="text-2xl font-bold">
                        {{ __('Step 1)') }}
                    </h1>
                    <p class="mt-2 text-lg font-medium">
                        {{ __('Link your first Remote Server') }}
                    </p>
                    <div class="p-3 my-4 flex justify-between space-x-4">
                        <x-icons.ubuntu class="h-14 w-14"/>
                        <x-icons.debian class="h-14 w-14"/>
                        <x-icons.tux class="h-14 w-14"/>
                    </div>
                    <div class="text-sm">
                        {{ __('We support Ubuntu and Debian distributions primarily.') }}
                    </div>
                    <div>
                        <a href="{{ route('remote-servers.create') }}" wire:navigate>
                            <x-primary-button class="mt-4" centered fat>
                                {{ __('Link Remote Server') }}
                                @svg('heroicon-o-arrow-right', 'h-5 w-5 ml-2 inline')
                            </x-primary-button>
                        </a>
                    </div>
                </div>
                <div class="bg-white rounded-[0.70rem] px-5 py-5 text-gray-950 col-span-4">
                    <h1 class="text-2xl font-bold">
                        {{ __('Step 2)') }}
                    </h1>
                    <p class="mt-2 text-lg font-medium">
                        {{ __('Connect a Backup Destination') }}
                    </p>
                    <div class="p-3 my-4 flex justify-between space-x-4">
                        <x-icons.aws class="h-14 w-14"/>
                        <x-icons.upcloud class="h-14 w-14"/>
                        <x-icons.gcp class="h-14 w-14"/>
                    </div>
                    <div class="text-sm">
                        {{ __('Add your S3 bucket API details to store your backups safely.') }}
                    </div>
                    <div>
                        @if (Auth::user()->remoteServers->isEmpty())
                            <div class="my-3 bg-red-50 rounded-lg p-2.5">
                                @svg('heroicon-o-exclamation-triangle', 'h-5 w-5 text-red-500 mr-2 inline')
                                <span class="text-red-500 text-sm">{{ __('You need to link a Remote Server first.') }}</span>
                            </div>
                            <x-primary-button
                                class="mt-4 cursor-not-allowed bg-opacity-50 hover:bg-opacity-50 focus:bg-opacity-50"
                                centered fat disabled>
                                {{ __('Add your Backup Destination') }}
                                @svg('heroicon-o-arrow-right', 'h-5 w-5 ml-2 inline')
                            </x-primary-button>
                        @else
                            <a href="{{ route('backup-destinations.create') }}" wire:navigate>
                                <x-primary-button class="mt-4" centered fat>
                                    {{ __('Add another Backup Destination') }}
                                    @svg('heroicon-o-arrow-right', 'h-5 w-5 ml-2 inline')
                                </x-primary-button>
                        @endif
                    </div>
                </div>
                <div class="bg-white rounded-[0.70rem] px-5 py-5 text-gray-950 col-span-4">
                    <h1 class="text-2xl font-bold">
                        {{ __('Step 3)') }}
                    </h1>
                    <p class="mt-2 text-lg font-medium">
                        {{ __('Make your first Backup Task') }}
                    </p>
                    <div class="my-4 text-gray-900 text-base font-medium">
                        {{ __('Effortlessly set up your Linux server backups to secure S3 buckets. Schedule for continuous data protection.') }}
                    </div>
                    <div class="text-sm">
                        {{ __('Easily control when your backups run and be notified of their progress.') }}
                    </div>
                    <div>
                        @if (Auth::user()->remoteServers->isEmpty() || Auth::user()->backupDestinations->isEmpty())
                            <div class="my-3 bg-red-50 rounded-lg p-2.5">
                                @svg('heroicon-o-exclamation-triangle', 'h-5 w-5 text-red-500 mr-2 inline')
                                <span
                                    class="text-red-500 text-sm">{{ __('You need to link a Backup Destination first.') }}</span>
                            </div>
                            <x-primary-button
                                class="mt-4 cursor-not-allowed bg-opacity-50 hover:bg-opacity-50 focus:bg-opacity-50"
                                centered fat disabled>
                                {{ __('Create your first Backup Task') }}
                                @svg('heroicon-o-arrow-right', 'h-5 w-5 ml-2 inline')
                            </x-primary-button>
                        @else
                            <a href="{{ route('backup-tasks.create') }}" wire:navigate>
                                <x-primary-button class="mt-6" centered fat>
                                    {{ __('Create your first Backup Task') }}
                                    @svg('heroicon-o-arrow-right', 'h-5 w-5 ml-2')
                                </x-primary-button>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="text-center md:my-16">
                <x-application-logo class="h-32 w-32 inline"/>
            </div>
        </div>
    </div>
</div>
