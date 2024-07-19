<div>
    <x-modal name="backup-task-{{ $backupTaskId }}" wire:key="backup-task-{{ $backupTaskId }}">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Viewing latest log for ":label".', ['label' => $backupTask?->label ?? __('Unknown')]) }}
            </h2>

            @if (app()->isLocal())
                <!-- Debug Info -->
                <div x-data="{ showDebug: false }" class="mt-4">
                    <button @click="showDebug = !showDebug" class="flex items-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                    <span x-show="!showDebug">
                        @svg('heroicon-o-eye', 'w-4 h-4 mr-1')
                        {{ __('Show Debug Info') }}
                    </span>
                        <span x-show="showDebug">
                        @svg('heroicon-o-eye-slash', 'w-4 h-4 mr-1')
                        {{ __('Hide Debug Info') }}
                    </span>
                    </button>
                    <div x-show="showDebug" x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-95"
                         class="mt-2 p-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs rounded-md shadow-sm">
                        <ul class="space-y-1">
                            <li class="flex items-center">
                                @svg('heroicon-o-identification', 'w-4 h-4 mr-2 text-gray-500 dark:text-gray-400')
                                <span>Component ID: {{ $this->getId() }}</span>
                            </li>
                            <li class="flex items-center">
                                @svg('heroicon-o-clipboard-document-list', 'w-4 h-4 mr-2 text-gray-500 dark:text-gray-400')
                                <span>Backup Task ID: {{ $backupTaskId }}</span>
                            </li>
                            <li class="flex items-center">
                                @svg('heroicon-o-arrow-path', 'w-4 h-4 mr-2 text-gray-500 dark:text-gray-400')
                                <span>Is Streaming: {{ $isStreaming ? 'Yes' : 'No' }}</span>
                            </li>
                            <li class="flex items-center">
                                @svg('heroicon-o-clock', 'w-4 h-4 mr-2 text-gray-500 dark:text-gray-400')
                                <span>Is Loading: {{ $isLoading ? 'Yes' : 'No' }}</span>
                            </li>
                            <li class="flex items-center">
                                @svg('heroicon-o-document-text', 'w-4 h-4 mr-2 text-gray-500 dark:text-gray-400')
                                <span>Log Output Length: {{ strlen($logOutput) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            @endif

            <div class="my-5">
                @if ($isLoading)
                    <div class="flex flex-col items-center justify-center">
                        <x-spinner class="text-gray-800 dark:text-gray-200 h-14 w-14"/>
                        <div class="text-gray-800 dark:text-gray-200 font-medium my-4 text-lg">{{ __('Loading log data...') }}</div>
                    </div>
                @else
                    <div wire:poll.5s="refresh">
                        <x-textarea id="logOutput" readonly wire:model.live="logOutput" class="pre text-sm text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-800 font-mono" rows="16" wrap>
                        </x-textarea>
                        @if ($isStreaming)
                            <div class="flex items-center text-gray-600 dark:text-gray-400 text-sm mt-2">
                                @svg('heroicon-o-arrow-path', 'w-4 h-4 mr-2 animate-spin')
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
