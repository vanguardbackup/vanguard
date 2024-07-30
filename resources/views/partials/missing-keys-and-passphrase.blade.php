@if (!ssh_keys_exist() || !config('app.ssh.passphrase'))
    <div x-data="{
        show: true,
        copied: false,
        copyCommand() {
            navigator.clipboard.writeText(this.$refs.command.textContent);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        }
    }"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="bg-gradient-to-r from-red-500/90 to-red-600/90 text-white relative shadow-lg"
         role="alert"
         aria-live="assertive">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between">
                <div class="flex items-center w-full sm:w-auto mb-4 sm:mb-0">
                    <span class="flex p-2 rounded-full bg-red-700/50 backdrop-blur-sm">
                        @svg('heroicon-o-exclamation-triangle', 'h-6 w-6 text-white')
                    </span>
                    <p class="ml-3 font-medium text-sm sm:text-base">
                        @if (!ssh_keys_exist())
                            {{ __('Warning! SSH key missing.') }}
                        @else
                            {{ __('Warning! SSH passphrase not set.') }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                    @if (!ssh_keys_exist())
                        <button
                            @click="$dispatch('open-modal', 'ssh-key-generation')"
                            class="flex-grow sm:flex-grow-0 bg-white/10 hover:bg-white/20 backdrop-blur-sm px-6 py-2 text-sm font-medium text-white rounded-full border border-white/25 focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-red-600 transition-all duration-150 ease-out hover:shadow-lg hover:-translate-y-0.5"
                        >
                            @svg('heroicon-o-command-line', ['class' => 'h-4 w-4 inline mr-2'])
                            {{ __('Show Command') }}
                        </button>

                        <x-modal name="ssh-key-generation" :focusable="true" maxWidth="lg">
                            <x-slot name="title">
                                {{ __('SSH Key Generation Command') }}
                            </x-slot>
                            <x-slot name="description">
                                {{ __('Easy, straightforward instructions on how to generate your SSH keys.') }}
                            </x-slot>
                            <x-slot name="icon">
                                heroicon-o-command-line
                            </x-slot>
                            <div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Run this command in your terminal to generate SSH keys:') }}
                                    </p>
                                    <div class="mt-3 relative">
                                        <div class="bg-gray-100 dark:bg-gray-700 rounded-md p-3 font-mono text-sm text-gray-800 dark:text-gray-200 break-all sm:break-normal">
                                            <code x-ref="command">php artisan vanguard:generate-ssh-key</code>
                                        </div>
                                        <button
                                            @click="copyCommand"
                                            class="absolute top-2 right-2 p-1 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors duration-150"
                                            :aria-label="copied ? '{{ __('Copied!') }}' : '{{ __('Copy to Clipboard') }}'"
                                        >
                                            <span x-show="!copied" aria-hidden="true">
                                                @svg('heroicon-o-clipboard-document', 'h-5 w-5')
                                            </span>
                                            <span x-show="copied" x-cloak aria-hidden="true">
                                                @svg('heroicon-o-clipboard-document-check', 'h-5 w-5')
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <x-secondary-button @click="$dispatch('close')" centered>
                                        {{ __('Close') }}
                                    </x-secondary-button>
                                </div>
                            </div>
                        </x-modal>

                        <div class="w-full sm:w-auto">
                            @livewire('other.generate-ssh-keys-button')
                        </div>
                    @else
                        <button
                            @click="$dispatch('open-modal', 'set-passphrase')"
                            class="flex-grow sm:flex-grow-0 bg-white/10 hover:bg-white/20 backdrop-blur-sm px-6 py-2 text-sm font-medium text-white rounded-full border border-white/25 focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-red-600 transition-all duration-150 ease-out hover:shadow-lg hover:-translate-y-0.5"
                        >
                            @svg('heroicon-o-key', ['class' => 'h-4 w-4 inline mr-2'])
                            {{ __('How to Set Passphrase') }}
                        </button>

                        <x-modal name="set-passphrase" :focusable="true"  maxWidth="lg">
                            <x-slot name="title">
                                {{ __('How to Set SSH Passphrase') }}
                            </x-slot>
                            <x-slot name="description">
                                {{ __('Easy, straightforward instructions on how to set your passphrase.') }}
                            </x-slot>
                            <x-slot name="icon">
                                heroicon-o-command-line
                            </x-slot>
                            <div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('To set the SSH passphrase:') }}</p>
                                    <ol class="list-decimal list-inside text-sm space-y-2 mt-2">
                                        <li>{{ __('Open your .env file') }}</li>
                                        <li>{{ __('Add or update the following line:') }}</li>
                                        <code class="block bg-gray-100 dark:bg-gray-700 p-2 rounded mt-1 text-xs break-all">SSH_PASSPHRASE=your_passphrase_here</code>
                                        <li>{{ __('Save the file and restart your application') }}</li>
                                    </ol>
                                </div>

                                <div class="mt-6">
                                    <x-secondary-button @click="$dispatch('close')" centered>
                                        {{ __('Close') }}
                                    </x-secondary-button>
                                </div>
                            </div>
                        </x-modal>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
