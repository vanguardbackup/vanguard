<div>
    <x-modal name="backup-task-{{ $backupTaskId }}" wire:key="backup-task-{{ $backupTaskId }}">
        <x-slot name="title">
            {{ __('Viewing latest log for ":label".', ['label' => $backupTask?->label ?? __('Unknown')]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('Viewing a Backup Task log.') }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-document-text
        </x-slot>
        <div>
            @if (app()->isLocal())
                <div
                    x-data="{ showDebug: false }"
                    class="mt-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <button
                        @click="showDebug = !showDebug"
                        class="w-full px-4 py-2 text-left text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/90 focus:outline-none transition-colors duration-200"
                    >
                        <div class="flex items-center justify-between">
                <span class="flex items-center">
                    <x-heroicon-o-bug-ant class="w-5 h-5 mr-2" />
                    {{ __('Debug Information') }}
                </span>
                            <x-heroicon-o-chevron-down
                                class="w-5 h-5 transition-transform duration-200"
                                ::class="{ 'transform rotate-180': showDebug }"
                            />
                        </div>
                    </button>

                    <div
                        x-show="showDebug"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 transform translate-y-0"
                        x-transition:leave-end="opacity-0 transform -translate-y-2"
                        class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm rounded-b-lg"
                    >
                        <ul class="space-y-2">
                            @php
                                $debugItems = [
                                    ['icon' => 'identification', 'label' => 'Component ID', 'value' => $this->getId()],
                                    ['icon' => 'clipboard-document-list', 'label' => 'Backup Task ID', 'value' => $backupTaskId],
                                    ['icon' => 'arrow-path', 'label' => 'Is Streaming', 'value' => $isStreaming ? 'Yes' : 'No'],
                                    ['icon' => 'clock', 'label' => 'Is Loading', 'value' => $isLoading ? 'Yes' : 'No'],
                                    ['icon' => 'document-text', 'label' => 'Log Output Length', 'value' => strlen($logOutput)],
                                ];
                            @endphp

                            @foreach ($debugItems as $item)
                                <li class="flex items-center justify-between">
                        <span class="flex items-center text-gray-600 dark:text-gray-400">
                            <x-dynamic-component
                                :component="'heroicon-o-' . $item['icon']"
                                class="w-4 h-4 mr-2"
                            />
                            {{ $item['label'] }}:
                        </span>
                                    <span class="font-medium">{{ $item['value'] }}</span>
                                </li>
                            @endforeach
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
