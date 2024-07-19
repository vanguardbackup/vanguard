<div>
    @if (!ssh_keys_exist())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('heroicon-o-exclamation-triangle', 'h-12 w-12 sm:h-16 sm:w-16 inline mr-1 text-red-600 dark:text-red-400')
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
            @if (!$showingConnectionView)
                <form wire:submit="submit">
                    @if (ssh_keys_exist())
                        <div class="mt-4" x-data="{ copied: false }">
                            <x-input-label for="ssh_key" :value="__('Public SSH Key')" class="mb-2"/>
                            <div class="relative bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                                <div class="p-4 text-sm font-mono text-gray-800 dark:text-gray-200 overflow-x-auto whitespace-pre-wrap break-all" x-ref="sshKey" style="max-height: 200px;">{{ $ourPublicKey }}</div>
                                <div class="absolute top-2 right-2 flex space-x-2">
                                    <button type="button"
                                            class="p-1 rounded-md bg-white dark:bg-gray-600 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                                            @click="
                                                navigator.clipboard.writeText($refs.sshKey.innerText);
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                            :class="{ 'bg-green-100 dark:bg-green-800': copied }"
                                    >
                                        <span x-show="!copied">
                                            @svg('heroicon-o-clipboard-document', 'h-5 w-5')
                                        </span>
                                        <span x-show="copied" x-cloak>
                                            @svg('heroicon-o-clipboard-document-check', 'h-5 w-5 text-green-500')
                                        </span>
                                    </button>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Copy the SSH key and run it on your intended remote server to give us secure access. We do not recommend using the root user.') }}
                            </p>
                        </div>
                    @endif

                    <div class="mt-6 bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                        <h4 class="font-medium text-sm text-gray-800 dark:text-gray-200 mb-3">
                            {{ __('Are you using server management?') }}
                        </h4>
                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                            <x-secondary-button type="button" wire:click="usingServerProvider('ploi')" class="w-full sm:w-auto justify-center">
                                @svg('heroicon-o-server', 'w-5 h-5 mr-2') Ploi
                            </x-secondary-button>
                            <x-secondary-button type="button" wire:click="usingServerProvider('forge')" class="w-full sm:w-auto justify-center">
                                @svg('heroicon-o-server', 'w-5 h-5 mr-2') Laravel Forge
                            </x-secondary-button>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <x-input-label for="label" :value="__('Label')"/>
                            <x-text-input id="label" class="mt-1 block w-full" type="text" wire:model="label" name="label" autofocus />
                            <x-input-error :messages="$errors->get('label')" class="mt-2"/>
                        </div>

                        <div class="sm:col-span-3">
                            <x-input-label for="host" :value="__('Host')"/>
                            <x-text-input id="host" class="mt-1 block w-full" type="text" wire:model="host" name="host"/>
                            <x-input-error :messages="$errors->get('host')" class="mt-2"/>
                        </div>

                        <div class="sm:col-span-3">
                            <x-input-label for="port" :value="__('SSH Port')"/>
                            <x-text-input id="port" class="mt-1 block w-full" type="text" wire:model="port" name="port"/>
                            <x-input-error :messages="$errors->get('port')" class="mt-2"/>
                        </div>

                        <div class="sm:col-span-3">
                            <x-input-label for="username" :value="__('SSH Username')"/>
                            <x-text-input id="username" class="mt-1 block w-full" type="text" wire:model="username" name="username" placeholder="{{ __('user') }}"/>
                            <x-input-error :messages="$errors->get('username')" class="mt-2"/>
                        </div>

                        <div class="sm:col-span-6" x-data="{ show: false }">
                            <x-input-label for="databasePassword" :value="__('Database Password')"/>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <x-text-input id="databasePassword"
                                              class="block w-full pr-10"
                                              x-bind:type="show ? 'text' : 'password'"
                                              wire:model="databasePassword"
                                              name="databasePassword"/>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <button @click="show = !show" type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <span x-show="!show">@svg('heroicon-o-eye', 'h-5 w-5')</span>
                                        <span x-show="show">@svg('heroicon-o-eye-slash', 'h-5 w-5')</span>
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
                        <div class="mt-6 max-w-3xl mx-auto">
                            <div class="flex flex-col sm:flex-row sm:space-x-5 space-y-4 sm:space-y-0">
                                <div class="w-full sm:w-4/6">
                                    <x-primary-button type="submit" class="mt-4 w-full" centered action="submit" loadingText="Verifying Connection...">
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
                    @svg('heroicon-o-check-circle', 'w-12 h-12 sm:w-16 sm:h-16 text-green-500 my-3 inline')
                    <h1 class="text-gray-900 dark:text-white text-xl sm:text-2xl font-semibold">
                        {{ __('Connection Successful') }}
                    </h1>
                    <p class="text-base sm:text-lg text-gray-700 dark:text-gray-100">
                        {{ __(':app has connected to your remote server!', ['app' => config('app.name')]) }}
                    </p>
                    <hr class="my-4"/>
                    <a href="{{ route('backup-destinations.index') }}" wire:navigate>
                        <x-primary-button type="button" class="mt-7 w-full sm:w-auto" centered>
                            {{ __('Configure Backup Destination') }}
                        </x-primary-button>
                    </a>
                </div>
            @else
                <div class="space-y-2 text-center">
                    @svg('heroicon-o-x-circle', 'w-12 h-12 sm:w-16 sm:h-16 text-red-500 my-3 inline')
                    <h1 class="text-gray-900 dark:text-white text-xl sm:text-2xl font-semibold">
                        {{ __('Connection Failed') }}
                    </h1>
                    <p class="text-base sm:text-lg text-gray-700 dark:text-gray-100">
                        {{ __('Unfortunately :app was not able to connect. Find the error message below.', ['app' => config('app.name')]) }}
                    </p>
                    <div>
                        <x-input-label for="error" :value="__('Error')"/>
                        <x-textarea rows="6">{{ $connectionError }}</x-textarea>
                    </div>
                    <hr class="my-4"/>
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
