{{-- blade-formatter-disable --}}
<div>
    @if (! ssh_keys_exist())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('hugeicons-alert-02', 'mr-1 inline h-12 w-12 text-red-600 sm:h-16 sm:w-16 dark:text-red-400')
            </x-slot>
            <x-slot name="title">
                {{ __('Error! Unable to locate an SSH Key.') }}
            </x-slot>
            <x-slot name="description">
                {{ __(':app cannot connect to remote servers without having an SSH key and passphrase set.', ['app' => config('app.name')]) }}
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('overview') }}" wire:navigate>
                    <x-primary-button type="button" class="mt-4 w-full sm:w-auto">
                        {{ __('← Return to Home') }}
                    </x-primary-button>
                </a>
            </x-slot>
        </x-no-content>
    @else
        <x-form-wrapper>
            <x-slot name="title">
                {{ __('Add Remote Server') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Create a new remote server.') }}
            </x-slot>
            <x-slot name="icon">hugeicons-hard-drive</x-slot>
            @if (! $showingConnectionView)
                <form wire:submit="submit">
                    @if (ssh_keys_exist())
                        <div class="mt-4" x-data="{ copied: false }">
                            <x-input-label for="ssh_key" :value="__('Public SSH Key')" class="mb-2"/>
                            <div
                                class="relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700"
                            >
                                <div
                                    class="overflow-x-auto whitespace-pre-wrap break-all p-4 font-mono text-sm text-gray-800 dark:text-gray-200"
                                    x-ref="sshKey"
                                    style="max-height: 200px"
                                >{{ $ourPublicKey }}</div>
                                <div class="absolute right-2 top-2 flex space-x-2">
                                    <button
                                        type="button"
                                        class="rounded-md bg-white p-1 text-gray-400 transition-colors duration-200 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-600 dark:hover:text-gray-300"
                                        @click="
                                                navigator.clipboard.writeText($refs.sshKey.innerText);
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                        :class="{ 'bg-green-100 dark:bg-green-800': copied }"
                                    >
                                        <span x-show="!copied">
                                            @svg('hugeicons-task-01', 'h-5 w-5')
                                        </span>
                                        <span x-show="copied" x-cloak>
                                            @svg('hugeicons-task-done-02', 'h-5 w-5 text-green-500')
                                        </span>
                                    </button>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Copy the SSH key and run it on your intended remote server to give us secure access. We do not recommend using the root user.') }}
                            </p>
                        </div>
                    @endif

                    <div class="mt-6 rounded-lg bg-gray-100 p-4 dark:bg-gray-700">
                        <h4 class="mb-3 text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ __('Are you using server management?') }}
                        </h4>
                        <div class="flex flex-col space-y-2 sm:flex-row sm:space-x-3 sm:space-y-0">
                            <x-secondary-button
                                type="button"
                                wire:click="usingServerProvider('ploi')"
                                class="w-full justify-center sm:w-auto"
                            >
                                @svg('hugeicons-cloud', 'mr-2 h-5 w-5')
                                Ploi
                            </x-secondary-button>
                            <x-secondary-button
                                type="button"
                                wire:click="usingServerProvider('forge')"
                                class="w-full justify-center sm:w-auto"
                            >
                                @svg('hugeicons-cloud', 'mr-2 h-5 w-5')
                                Laravel Forge
                            </x-secondary-button>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <x-input-label for="label" :value="__('Label')"/>
                            <x-text-input
                                id="label"
                                class="mt-1 block w-full"
                                type="text"
                                wire:model="label"
                                name="label"
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('label')" class="mt-2"/>
                        </div>

                        <div class="sm:col-span-3">
                            <x-input-label for="host" :value="__('Host')"/>
                            <x-text-input
                                id="host"
                                class="mt-1 block w-full"
                                type="text"
                                wire:model="host"
                                name="host"
                            />
                            <x-input-error :messages="$errors->get('host')" class="mt-2"/>
                        </div>

                        <div class="sm:col-span-3">
                            <x-input-label for="port" :value="__('SSH Port')"/>
                            <x-text-input
                                id="port"
                                class="mt-1 block w-full"
                                type="text"
                                wire:model="port"
                                name="port"
                            />
                            <x-input-error :messages="$errors->get('port')" class="mt-2"/>
                        </div>

                        <div class="sm:col-span-3">
                            <x-input-label for="username" :value="__('SSH Username')"/>
                            <x-text-input
                                id="username"
                                class="mt-1 block w-full"
                                type="text"
                                wire:model="username"
                                name="username"
                                placeholder="{{ __('user') }}"
                            />
                            <x-input-error :messages="$errors->get('username')" class="mt-2"/>
                        </div>

                        <div class="sm:col-span-6" x-data="{ show: false }">
                            <x-input-label for="databasePassword" :value="__('Database Password')"/>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <x-text-input
                                    id="databasePassword"
                                    class="block w-full pr-10"
                                    x-bind:type="show ? 'text' : 'password'"
                                    wire:model="databasePassword"
                                    name="databasePassword"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <button
                                        @click="show = !show"
                                        type="button"
                                        class="text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        <span x-show="!show">
                                            @svg('hugeicons-view-off', 'h-5 w-5')
                                        </span>
                                        <span x-show="show">
                                            @svg('hugeicons-view', 'h-5 w-5')
                                        </span>
                                    </button>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('databasePassword')" class="mt-2"/>
                            <x-input-explain>
                                {{ __('The password is essential for performing database backup tasks. We encrypt the password upon receiving it.') }}
                            </x-input-explain>
                        </div>
                    </div>

                    <section>
                        <div class="mx-auto mt-6 max-w-3xl">
                            <div class="flex flex-col space-y-4 sm:flex-row sm:space-x-5 sm:space-y-0">
                                <div class="w-full sm:w-4/6">
                                    <x-primary-button
                                        type="submit"
                                        class="mt-4 w-full"
                                        centered
                                        action="submit"
                                        loadingText="Verifying Connection..."
                                    >
                                        {{ __('Test Connection') }}
                                    </x-primary-button>
                                </div>
                                <div class="w-full sm:w-2/6">
                                    <a href="{{ route('remote-servers.index') }}" wire:navigate class="block">
                                        <x-secondary-button type="button" class="mt-4 w-full" centered>
                                            {{ __('Cancel Setup') }}
                                        </x-secondary-button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>
            @elseif ($canConnectToRemoteServer)
                <div>
                    <div class="flex flex-col items-center space-y-4">

                        <div class="relative inline-flex items-center justify-center">
                            <div class="absolute h-16 w-16 sm:h-20 sm:w-20 animate-ping rounded-full bg-green-100 opacity-50 dark:bg-green-900"></div>
                            @svg('hugeicons-checkmark-circle-02', 'relative h-16 w-16 text-green-500 sm:h-20 sm:w-20')
                        </div>

                        <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white">
                            {{ __('Connection Successful!') }}
                        </h1>

                        <p class="text-center text-base text-gray-700 sm:text-lg dark:text-gray-200">
                            {{ __(':app has successfully established a secure connection to your remote server.', ['app' => config('app.name')]) }}
                        </p>

                        <div class="mt-4 w-full max-w-md rounded-lg bg-gray-50 p-4 shadow dark:bg-gray-700">
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">
                                {{ __('Connection Details') }}
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-600 dark:text-gray-300">{{ __('Server Label') }}:</span>
                                    <span class="text-gray-800 dark:text-white">{{ $label }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-600 dark:text-gray-300">{{ __('Host') }}:</span>
                                    <span class="text-gray-800 dark:text-white">{{ $host }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-600 dark:text-gray-300">{{ __('Port') }}:</span>
                                    <span class="text-gray-800 dark:text-white">{{ $port }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 w-full max-w-md rounded-lg bg-blue-50 p-4 dark:bg-blue-900/30">
                            <div class="flex items-start space-x-3">
                                @svg('hugeicons-idea-01', 'h-6 w-6 flex-shrink-0 text-blue-500 dark:text-blue-400')
                                <div>
                                    <h3 class="font-medium text-blue-800 dark:text-blue-300">{{ __('Next Steps') }}</h3>
                                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-200">
                                        {{ __('Configure your backup destination to start protecting your data. You can set up automated backups and customize your schedule.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 w-full pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                                <a href="{{ route('backup-destinations.index') }}" wire:navigate class="block">
                                    <x-primary-button type="button" class="w-full sm:w-auto justify-center">
                                        @svg('hugeicons-settings-02', 'mr-2 h-5 w-5 inline')
                                        {{ __('Configure Backup Destination') }}
                                    </x-primary-button>
                                </a>
                                <a href="{{ route('remote-servers.index') }}" wire:navigate class="block">
                                    <x-secondary-button type="button" class="w-full sm:w-auto justify-center">
                                        {{ __('View All Servers') }}
                                    </x-secondary-button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div>
                    <div class="mb-6 text-center">
                        <div class="mb-4 inline-flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/40">
                            @svg('hugeicons-cancel-circle', 'h-10 w-10 text-red-500')
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ __('Connection Failed') }}
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-300 max-w-lg mx-auto">
                            {{ __('Unfortunately :app was not able to establish a connection to your server.', ['app' => config('app.name')]) }}
                        </p>
                    </div>
                    <div class="mb-6 rounded-lg bg-white border border-red-200 shadow-sm dark:bg-gray-750 dark:border-red-800">
                        <div class="border-b border-red-100 bg-red-50 px-4 py-3 dark:bg-red-900/20 dark:border-red-800 rounded-t-lg">
                            <div class="flex items-center">
                                @svg('hugeicons-alert-02', 'h-5 w-5 text-red-500 mr-2')
                                <h2 class="font-semibold text-red-700 dark:text-red-300">
                                    {{ __('Error Details') }}
                                </h2>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="rounded bg-gray-50 p-3 font-mono text-sm text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                {{ $connectionError }}
                            </div>
                        </div>
                    </div>
                    <div class="mb-6 rounded-lg bg-white border border-blue-200 shadow-sm dark:bg-gray-750 dark:border-blue-800">
                        <div class="border-b border-blue-100 bg-blue-50 px-4 py-3 dark:bg-blue-900/20 dark:border-blue-800 rounded-t-lg">
                            <div class="flex items-center">
                                @svg('hugeicons-idea-01', 'h-5 w-5 text-blue-500 mr-2')
                                <h2 class="font-semibold text-blue-700 dark:text-blue-300">
                                    {{ __('Troubleshooting Tips') }}
                                </h2>
                            </div>
                        </div>
                        <div class="p-4">
                            <ul class="space-y-3 text-gray-700 dark:text-gray-300">
                                @if (str_contains($connectionError, 'timeout'))
                                    <li class="flex items-start">
                                        <span class="mr-2 text-blue-500">•</span>
                                        <span>{{ __('Ensure the server is online and accessible from your network.') }}</span>
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-blue-500">•</span>
                                        <span>{{ __('Verify the IP address and port are correct.') }}</span>
                                    </li>
                                @elseif (str_contains($connectionError, 'authentication'))
                                    <li class="flex items-start">
                                        <span class="mr-2 text-blue-500">•</span>
                                        <span>{{ __('Check that your SSH username and public key are correctly configured.') }}</span>
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-blue-500">•</span>
                                        <span>{{ __('Ensure the server allows SSH access for the provided user.') }}</span>
                                    </li>
                                @else
                                    <li class="flex items-start">
                                        <span class="mr-2 text-blue-500">•</span>
                                        <span>{{ __('Double-check all connection details, including the IP address, port, and credentials.') }}</span>
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-blue-500">•</span>
                                        <span>{{ __('Ensure the server firewall allows SSH connections.') }}</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="mb-6 rounded-lg bg-white border border-gray-200 shadow-sm dark:bg-gray-750 dark:border-gray-700">
                        <div class="border-b border-gray-100 bg-gray-50 px-4 py-3 dark:bg-gray-700/20 dark:border-gray-700 rounded-t-lg">
                            <div class="flex items-center">
                                @svg('hugeicons-help-circle', 'h-5 w-5 text-gray-500 mr-2')
                                <h2 class="font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Common Connection Issues') }}
                                </h2>
                            </div>
                        </div>
                        <div class="p-4">
                            <dl class="space-y-4 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-700 dark:text-gray-200">{{ __('SSH Key Not Added') }}</dt>
                                    <dd class="mt-1 text-gray-600 dark:text-gray-400">{{ __('Make sure you have added the public SSH key to your server\'s authorized_keys file.') }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-700 dark:text-gray-200">{{ __('Firewall Restrictions') }}</dt>
                                    <dd class="mt-1 text-gray-600 dark:text-gray-400">{{ __('Check if your server\'s firewall is blocking SSH connections on the specified port.') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse sm:flex-row sm:justify-center space-y-4 space-y-reverse sm:space-y-0 sm:space-x-4">
                        <a href="{{ route('remote-servers.index') }}" wire:navigate class="rounded-md">
                            <x-secondary-button type="button" class="w-full sm:w-auto justify-center">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                        </a>
                        <x-primary-button type="button" class="w-full sm:w-auto justify-center" wire:click="returnToForm">
                            @svg('hugeicons-edit-02', 'mr-2 h-4 w-4 inline')
                            {{ __('Edit Connection Details') }}
                        </x-primary-button>
                    </div>
                </div>
            @endif
        </x-form-wrapper>
    @endif
</div>

<script>
    document.addEventListener('livewire:navigated', function () {
        Alpine.start();
    });
</script>
