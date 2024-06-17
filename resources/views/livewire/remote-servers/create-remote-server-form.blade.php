<div>
    @if (!ssh_keys_exist())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('heroicon-o-exclamation-triangle', 'h-16 w-16 inline mr-1 text-red-600 dark:text-red-400')
            </x-slot>
            <x-slot name="title">
                {{ __('Error! Unable to locate an SSH Key.') }}
            </x-slot>
            <x-slot name="description">
                {{ __(':app cannot connect to remote servers without having an SSH key and passphrase set.', ['app' => config('app.name')]) }}
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('overview') }}" wire:navigate>
                    <x-primary-button type="button" class="mt-4">
                        {{ __('← Return to Home') }}
                    </x-primary-button>
                </a>
            </x-slot>
        </x-no-content>
    @else
        <div class="flex justify-end">
            <div>
                <div class="font-medium text-sm text-gray-800 dark:text-gray-200">
                    {{ __('Are you using server management?') }}
                </div>
                <div class="flex space-x-3 my-1.5">
                    <x-secondary-button type="button" wire:click="usingServerProvider('ploi')">
                        {{ __('Ploi') }}
                    </x-secondary-button>
                    <x-secondary-button type="button" wire:click="usingServerProvider('forge')">
                        {{ __('Laravel Forge') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
        <x-form-wrapper>
            @if (!$showingConnectionView)
                <form wire:submit="submit">
                    @if (ssh_keys_exist())
                        <div class="mt-4">
                            <x-input-label for="ssh_key" :value="__('Public SSH Key')"/>
                            <x-textarea rows="10" readonly id="public_key">{{ $ourPublicKey }}</x-textarea>
                            <div class="flex justify-evenly my-3.5">
                                <div class="mt-2">
                                    <x-secondary-button class="btn" data-clipboard-target="#public_key" type="button"
                                                        id="copyButton">
                                        <span id="copyIcon" class="inline">
                                              @svg('heroicon-o-clipboard-document', 'h-5 w-5 mr-1')
                                        </span>
                                        <span id="copiedIcon" class="hidden">
                                            @svg('heroicon-o-clipboard-document-check', 'h-5 w-5 inline mr-1')
                                        </span>
                                        <span id="copyText" class="inline">
                                             {{ __('Copy') }}
                                        </span>
                                        <span id="copiedText" class="hidden">
                                            {{ __('Copied!') }}
                                        </span>
                                    </x-secondary-button>
                                    <script>
                                        document.addEventListener('livewire:navigated', function () {
                                            new ClipboardJS('.btn');
                                            document.getElementById('copyButton').addEventListener('click', function () {
                                                document.getElementById('copiedIcon').classList.remove('hidden');
                                                document.getElementById('copyIcon').classList.add('hidden');
                                                document.getElementById('copiedText').classList.remove('hidden');
                                                document.getElementById('copyText').classList.add('hidden');
                                            });
                                        });
                                    </script>
                                </div>
                                <div>
                                    <div
                                        class="mx-3 py-3.5 px-4 bg-gray-200 dark:bg-gray-600 dark:text-gray-200 text-gray-600 border-l-4 border-gray-600 dark:border-gray-300 font-medium">
                                    <span>
                                        {{ __('Copy the SSH key and run it on your intended remote server to give us secure access. We do not recommend using the root user.') }}
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="mt-4">
                        <x-input-label for="label" :value="__('Label')"/>
                        <x-text-input id="label" class="block mt-1 w-full" type="text" wire:model="label" name="label"
                                      autofocus
                                      placeholder="{{ __('sunny-village') }}"/>
                        <x-input-error :messages="$errors->get('label')" class="mt-2"/>
                    </div>
                    <div class="mt-4">
                        <div class="flex space-x-4">
                            <div class="w-1/2">
                                <x-input-label for="host" :value="__('Host')"/>
                                <x-text-input id="host" class="block mt-1 w-full" type="text" wire:model="host"
                                              name="host"/>
                                <x-input-error :messages="$errors->get('host')" class="mt-2"/>
                            </div>
                            <div class="w-1/2">
                                <x-input-label for="port" :value="__('SSH Port')"/>
                                <x-text-input id="port" class="block mt-1 w-full" type="text" wire:model="port"
                                              name="port"/>
                                <x-input-error :messages="$errors->get('port')" class="mt-2"/>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="username" :value="__('SSH Username')"/>
                        <x-text-input id="username" class="block mt-1 w-full" type="text" wire:model="username"
                                      name="username" placeholder="{{ __('user') }}"/>
                        <x-input-error :messages="$errors->get('username')" class="mt-2"/>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="databasePassword" :value="__('Database Password')"/>
                        <x-text-input id="databasePassword" class="block mt-1 w-full" type="password"
                                      wire:model="databasePassword"
                                      name="databasePassword"/>
                        <x-input-error :messages="$errors->get('databasePassword')" class="mt-2"/>
                        <x-input-explain>
                            {{ __('The password is essential for performing database backup tasks. We encrypt the password upon receiving it.') }}
                        </x-input-explain>
                    </div>
                    <section>
                        <div class="mt-6 max-w-3xl mx-auto">
                            <div class="flex space-x-5">
                                <div class="w-4/6">
                                    <x-primary-button type="submit" class="mt-4" centered action="submit" loadingText="Verifying Connection...">
                                        {{ __('Test Connection') }}
                                    </x-primary-button>
                                </div>
                                <div class="w-2/6 ml-2">
                                    <a href="{{ route('remote-servers.index') }}" wire:navigate>
                                        <x-secondary-button type="button" class="mt-4" centered>
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
                    @svg('heroicon-o-check-circle', 'w-16 h-16 text-green-500 my-3 inline')
                    <h1 class="text-gray-900 dark:text-white text-2xl font-semibold">
                        {{ __('Connection Successful') }}
                    </h1>
                    <p class="text-lg text-gray-700 dark:text-gray-100">
                        {{ __(':app has connected to your remote server!', ['app' => config('app.name')]) }}
                    </p>
                    <hr class="my-4"/>
                    <a href="{{ route('backup-destinations.index') }}" wire:navigate>
                        <x-primary-button type="button" class="mt-7" centered>
                            {{ __('Configure Backup Destination') }}
                        </x-primary-button>
                    </a>
                </div>
            @else
                <div class="space-y-2 text-center">
                    @svg('heroicon-o-x-circle', 'w-16 h-16 text-red-500 my-3 inline')
                    <h1 class="text-gray-900 dark:text-white text-2xl font-semibold">
                        {{ __('Connection Failed') }}
                    </h1>
                    <p class="text-lg text-gray-700 dark:text-gray-100">
                        {{ __('Unfortunately :app was not able to connect. Find the error message below.', ['app' => config('app.name')]) }}
                    </p>
                    <div>
                        <x-input-label for="error" :value="__('Error')"/>
                        <x-textarea rows="6">{{ $connectionError }}</x-textarea>
                    </div>
                    <hr class="my-4"/>
                    <x-secondary-button type="button" class="mt-7" centered wire:click="returnToForm">
                        {{ __('Edit Connection Details') }}
                    </x-secondary-button>
                </div>
            @endif
        </x-form-wrapper>
    @endif
</div>
