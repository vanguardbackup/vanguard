@if (! ssh_keys_exist() || ! config('app.ssh.passphrase'))
    <div
        x-data="{
            show: true,
            copied: false,
            copyCommand() {
                navigator.clipboard.writeText(this.$refs.command.textContent)
                this.copied = true
                setTimeout(() => (this.copied = false), 2000)
            },
        }"
        x-show="show"
        x-transition:enter="transition duration-300 ease-out"
        x-transition:enter-start="-translate-y-2 transform opacity-0"
        x-transition:enter-end="translate-y-0 transform opacity-100"
        x-transition:leave="transition duration-300 ease-in"
        x-transition:leave-start="translate-y-0 transform opacity-100"
        x-transition:leave-end="-translate-y-2 transform opacity-0"
        class="relative bg-gradient-to-r from-red-500/90 to-red-600/90 text-white shadow-lg"
        role="alert"
        aria-live="assertive"
    >
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between sm:flex-row">
                <div class="mb-4 flex w-full items-center sm:mb-0 sm:w-auto">
                    <span class="flex rounded-full bg-red-700/50 p-2 backdrop-blur-sm">
                        @svg('hugeicons-alert-02', 'h-6 w-6 text-white')
                    </span>
                    <p class="ml-3 text-sm font-medium sm:text-base">
                        @if (! ssh_keys_exist())
                            {{ __('Warning! SSH key missing.') }}
                        @else
                            {{ __('Warning! SSH passphrase not set.') }}
                        @endif
                    </p>
                </div>
                <div
                    class="flex w-full flex-col items-stretch space-y-2 sm:w-auto sm:flex-row sm:items-center sm:space-x-3 sm:space-y-0"
                >
                    @if (! ssh_keys_exist())
                        <button
                            @click="$dispatch('open-modal', 'ssh-key-generation')"
                            class="flex-grow rounded-full border border-white/25 bg-white/10 px-6 py-2 text-sm font-medium text-white backdrop-blur-sm transition-all duration-150 ease-out hover:-translate-y-0.5 hover:bg-white/20 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-red-600 sm:flex-grow-0"
                        >
                            @svg('hugeicons-keyboard', ['class' => 'mr-2 inline h-4 w-4'])
                            {{ __('Show Command') }}
                        </button>

                        <x-modal name="ssh-key-generation" :focusable="true" maxWidth="lg">
                            <x-slot name="title">
                                {{ __('SSH Key Generation Command') }}
                            </x-slot>
                            <x-slot name="description">
                                {{ __('Easy, straightforward instructions on how to generate your SSH keys.') }}
                            </x-slot>
                            <x-slot name="icon">hugeicons-keyboard</x-slot>
                            <div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Run this command in your terminal to generate SSH keys:') }}
                                    </p>
                                    <div class="relative mt-3">
                                        <div
                                            class="break-all rounded-md bg-gray-100 p-3 font-mono text-sm text-gray-800 sm:break-normal dark:bg-gray-700 dark:text-gray-200"
                                        >
                                            <code x-ref="command">php artisan vanguard:generate-ssh-key</code>
                                        </div>
                                        <button
                                            @click="copyCommand"
                                            class="absolute right-2 top-2 rounded-md p-1 text-gray-400 transition-colors duration-150 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:hover:text-gray-300 dark:focus:ring-offset-gray-800"
                                            :aria-label="copied ? '{{ __('Copied!') }}' : '{{ __('Copy to Clipboard') }}'"
                                        >
                                            <span x-show="!copied" aria-hidden="true">
                                                @svg('hugeicons-task-01', 'h-5 w-5')
                                            </span>
                                            <span x-show="copied" x-cloak aria-hidden="true">
                                                @svg('hugeicons-task-done-02', 'h-5 w-5')
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
                            class="flex-grow rounded-full border border-white/25 bg-white/10 px-6 py-2 text-sm font-medium text-white backdrop-blur-sm transition-all duration-150 ease-out hover:-translate-y-0.5 hover:bg-white/20 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-red-600 sm:flex-grow-0"
                        >
                            @svg('hugeicons-license', ['class' => 'mr-2 inline h-4 w-4'])
                            {{ __('How to Set Passphrase') }}
                        </button>

                        <x-modal name="set-passphrase" :focusable="true" maxWidth="lg">
                            <x-slot name="title">
                                {{ __('How to Set SSH Passphrase') }}
                            </x-slot>
                            <x-slot name="description">
                                {{ __('Easy, straightforward instructions on how to set your passphrase.') }}
                            </x-slot>
                            <x-slot name="icon">hugeicons-keyboard</x-slot>
                            <div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('To set the SSH passphrase:') }}
                                    </p>
                                    <ol class="mt-2 list-inside list-decimal space-y-2 text-sm">
                                        <li>
                                            {{ __('Open your .env file') }}
                                        </li>
                                        <li>
                                            {{ __('Add or update the following line:') }}
                                        </li>
                                        <code
                                            class="mt-1 block break-all rounded bg-gray-100 p-2 text-xs dark:bg-gray-700"
                                        >
                                            SSH_PASSPHRASE=your_passphrase_here
                                        </code>
                                        <li>
                                            {{ __('Save the file and restart your application') }}
                                        </li>
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
