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
                            <x-input-label for="ssh_key" :value="__('Public SSH Key')" class="mb-2" />
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
                            <x-input-label for="label" :value="__('Label')" />
                            <x-text-input
                                id="label"
                                class="mt-1 block w-full"
                                type="text"
                                wire:model="label"
                                name="label"
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('label')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-3">
                            <x-input-label for="host" :value="__('Host')" />
                            <x-text-input
                                id="host"
                                class="mt-1 block w-full"
                                type="text"
                                wire:model="host"
                                name="host"
                            />
                            <x-input-error :messages="$errors->get('host')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-3">
                            <x-input-label for="port" :value="__('SSH Port')" />
                            <x-text-input
                                id="port"
                                class="mt-1 block w-full"
                                type="text"
                                wire:model="port"
                                name="port"
                            />
                            <x-input-error :messages="$errors->get('port')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-3">
                            <x-input-label for="username" :value="__('SSH Username')" />
                            <x-text-input
                                id="username"
                                class="mt-1 block w-full"
                                type="text"
                                wire:model="username"
                                name="username"
                                placeholder="{{ __('user') }}"
                            />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-6" x-data="{ show: false }">
                            <x-input-label for="databasePassword" :value="__('Database Password')" />
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
                            <x-input-error :messages="$errors->get('databasePassword')" class="mt-2" />
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
                <div class="space-y-2 text-center">
                    @svg('hugeicons-checkmark-circle-02', 'my-3 inline h-12 w-12 text-green-500 sm:h-16 sm:w-16')
                    <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                        {{ __('Connection Successful') }}
                    </h1>
                    <p class="text-base text-gray-700 sm:text-lg dark:text-gray-100">
                        {{ __(':app has connected to your remote server!', ['app' => config('app.name')]) }}
                    </p>
                    <hr class="my-4" />
                    <a href="{{ route('backup-destinations.index') }}" wire:navigate>
                        <x-primary-button type="button" class="mt-7 w-full sm:w-auto" centered>
                            {{ __('Configure Backup Destination') }}
                        </x-primary-button>
                    </a>
                </div>
            @else
                <div class="space-y-2 text-center">
                    @svg('hugeicons-cancel-circle', 'my-3 inline h-12 w-12 text-red-500 sm:h-16 sm:w-16')
                    <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                        {{ __('Connection Failed') }}
                    </h1>
                    <p class="text-base text-gray-700 sm:text-lg dark:text-gray-100">
                        {{ __('Unfortunately :app was not able to connect. Find the error message below.', ['app' => config('app.name')]) }}
                    </p>
                    <div>
                        <x-input-label for="error" :value="__('Error')" />
                        <x-textarea rows="6">
                            {{ $connectionError }}
                        </x-textarea>
                    </div>
                    <hr class="my-4" />
                    <x-secondary-button type="button" class="mt-7 w-full sm:w-auto" centered wire:click="returnToForm">
                        {{ __('Edit Connection Details') }}
                    </x-secondary-button>
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
