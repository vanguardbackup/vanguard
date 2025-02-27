<div>
    <x-modal name="webhook-modal-{{ $backupTaskId }}" wire:key="webhook-modal-{{ $backupTaskId }}">
        <x-slot name="title">
            {{ __('Webhook URL for ":label"', ['label' => $backupTask?->label ?? __('Unknown')]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('View and manage your webhook URL for this backup task.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-link-03</x-slot>

        <div class="my-5">
            <x-input-label for="webhook_url" :value="__('Webhook URL')" />
            <div class="relative mt-1">
                <x-text-input
                    name="webhook_url"
                    wire:model="webhookUrl"
                    id="webhook_url"
                    type="text"
                    class="block w-full pr-10"
                    readonly
                />
                <button
                    type="button"
                    x-data="{ copied: false }"
                    x-on:click="
                        navigator.clipboard.writeText($el.closest('div').querySelector('input').value)
                        copied = true
                        setTimeout(() => (copied = false), 2000)
                    "
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                    title="{{ __('Copy to clipboard') }}"
                >
                    <span x-show="!copied" aria-hidden="true">
                        @svg('hugeicons-task-01', 'h-5 w-5')
                    </span>
                    <span x-show="copied" x-cloak aria-hidden="true">
                        @svg('hugeicons-task-done-02', 'h-5 w-5')
                    </span>
                </button>
            </div>
            <x-input-explain>
                {{ __('This URL can be used to trigger your backup task via a POST HTTP request.') }}
            </x-input-explain>

            <div class="mt-6 space-y-5">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('How to use this webhook') }}
                </h3>

                <div class="rounded-md bg-gray-100 p-4 dark:bg-gray-700/50">
                    <h4 class="mb-3 text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Example cURL command') }}
                    </h4>
                    <div class="relative mt-1">
                        <pre
                            class="overflow-x-auto rounded bg-white p-2 text-xs dark:bg-gray-800 dark:text-gray-300"
                        ><code>curl -X POST "{{ $webhookUrl }}" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"</code></pre>
                        <button
                            type="button"
                            x-data="{ copied: false }"
                            x-on:click="
                                navigator.clipboard.writeText(
                                    $el.closest('div').querySelector('code').innerText,
                                )
                                copied = true
                                setTimeout(() => (copied = false), 2000)
                            "
                            class="absolute right-3 top-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                            title="{{ __('Copy to clipboard') }}"
                        >
                            <span x-show="!copied" aria-hidden="true">
                                @svg('hugeicons-task-01', 'h-4 w-4')
                            </span>
                            <span x-show="copied" x-cloak aria-hidden="true">
                                @svg('hugeicons-task-done-02', 'h-4 w-4')
                            </span>
                        </button>
                    </div>
                </div>

                <p class="text-xs text-gray-600 dark:text-gray-400">
                    {{ __('This webhook accepts POST requests only and any other method will be rejected.') }}
                </p>

                <div class="mt-2 flex items-center text-xs text-blue-600 dark:text-blue-400">
                    <x-hugeicons-book-open-02 class="mr-1.5 h-4 w-4" />
                    <a
                        href="https://docs.vanguardbackup.com/backup-tasks#automated-triggering-via-webhook"
                        class="hover:underline"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {{ __('Learn more about Backup Task webhooks in our docs') }}
                    </a>
                </div>
            </div>

            <x-notice
                type="warning"
                :text="__('This token provides access to trigger your backup task. Please be careful with it!')"
                class="mt-6"
            />
        </div>

        <div class="flex space-x-5">
            <div class="w-4/6">
                <x-secondary-button x-on:click="$dispatch('close')" centered class="w-full">
                    {{ __('Close') }}
                </x-secondary-button>
            </div>
            <div class="w-2/6">
                <x-danger-button
                    wire:click="refreshToken"
                    wire:loading.attr="disabled"
                    wire:target="refreshToken"
                    centered
                    class="w-full"
                >
                    <span wire:loading.remove wire:target="refreshToken" class="flex items-center justify-center">
                        <x-hugeicons-arrow-reload-horizontal class="mr-2 h-4 w-4" />
                        {{ __('Regenerate Token') }}
                    </span>
                    <span wire:loading wire:target="refreshToken" class="flex items-center justify-center">
                        <x-spinner class="mr-2 h-4 w-4" />
                        {{ __('Regenerating...') }}
                    </span>
                </x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
