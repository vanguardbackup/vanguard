<div>
    <x-modal name="backup-task-{{ $backupTaskId }}" wire:key="backup-task-{{ $backupTaskId }}">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Viewing latest log for ":label".', ['label' => $backupTask?->label ?? __('Unknown')]) }}
            </h2>
            @if (app()->isLocal())
            <!-- Debug Info -->
            <div class="mt-4 p-2 bg-gray-100 text-gray-700 text-xs">
                <p>Component ID: {{ $this->getId() }}</p>
                <p>Backup Task ID: {{ $backupTaskId }}</p>
                <p>Is Streaming: {{ $isStreaming ? 'Yes' : 'No' }}</p>
                <p>Is Loading: {{ $isLoading ? 'Yes' : 'No' }}</p>
                <p>Log Output Length: {{ strlen($logOutput) }}</p>
            </div>
            @endif
            <div class="my-5">
                @if ($isLoading)
                    <div class="mx-auto">
                        <x-spinner class="text-gray-800 dark:text-gray-200 h-14 w-14 inline"/>
                        <div class="text-gray-800 dark:text-gray-200 font-medium my-4 text-lg">{{ __('Loading log data...') }}</div>
                    </div>
                @else
                    <div wire:poll.5s="refresh">
                        <x-textarea id="logOutput" readonly wire:model.live="logOutput" class="pre text-sm text-gray-800 bg-gray-50 font-mono" rows="16" wrap>
                        </x-textarea>
                        @if ($isStreaming)
                            <div class="text-gray-600 dark:text-gray-400 text-sm mt-2">
                                {{ __('Streaming live log data...') }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="mt-6">
                <x-secondary-button x-on:click="$dispatch('close')" centered>
                    {{ __('Close') }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>
