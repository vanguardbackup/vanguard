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
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700"
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
                {{ __('This URL can be used to trigger your backup task via HTTP requests.') }}
            </x-input-explain>

            <x-notice
                type="warning"
                :text="__('This token provides access to trigger your backup task. Treat it like a password and regenerate it if it becomes compromised.')"
                class="mt-4"
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
