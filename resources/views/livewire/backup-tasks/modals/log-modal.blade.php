<div>
    <x-modal name="backup-task-{{ $backupTaskId }}" wire:key="backup-task-{{ $backupTaskId }}">
        <x-slot name="title">
            {{ __('Viewing latest log for ":label".', ['label' => $backupTask?->label ?? __('Unknown')]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('Viewing a Backup Task log.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-license</x-slot>
        <div>
            @if (app()->isLocal())
                <div
                    x-data="{ showDebug: false }"
                    class="mt-4 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <button
                        @click="showDebug = !showDebug"
                        class="w-full px-4 py-2 text-left text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800/90"
                    >
                        <div class="flex items-center justify-between">
                            <span class="flex items-center">
                                <x-hugeicons-bug-02 class="mr-2 h-5 w-5" />
                                {{ __('Debug Information') }}
                            </span>
                            <x-hugeicons-arrow-right-01
                                class="h-5 w-5 transition-transform duration-200"
                                ::class="{ 'transform rotate-180': showDebug }"
                            />
                        </div>
                    </button>

                    <div
                        x-show="showDebug"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-start="-translate-y-2 transform opacity-0"
                        x-transition:enter-end="translate-y-0 transform opacity-100"
                        x-transition:leave="transition duration-200 ease-in"
                        x-transition:leave-start="translate-y-0 transform opacity-100"
                        x-transition:leave-end="-translate-y-2 transform opacity-0"
                        class="rounded-b-lg bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <ul class="space-y-2">
                            @php
                                $debugItems = [
                                    ['icon' => 'profile', 'label' => 'Component ID', 'value' => $this->getId()],
                                    ['icon' => 'task-01', 'label' => 'Backup Task ID', 'value' => $backupTaskId],
                                    ['icon' => 'refresh', 'label' => 'Is Streaming', 'value' => $isStreaming ? 'Yes' : 'No'],
                                    ['icon' => 'clock-01', 'label' => 'Is Loading', 'value' => $isLoading ? 'Yes' : 'No'],
                                    ['icon' => 'license', 'label' => 'Log Output Length', 'value' => strlen($logOutput)],
                                ];
                            @endphp

                            @foreach ($debugItems as $item)
                                <li class="flex items-center justify-between">
                                    <span class="flex items-center text-gray-600 dark:text-gray-400">
                                        <x-dynamic-component
                                            :component="'hugeicons-' . $item['icon']"
                                            class="mr-2 h-4 w-4"
                                        />
                                        {{ $item['label'] }}:
                                    </span>
                                    <span class="font-medium">
                                        {{ $item['value'] }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="my-5">
                @if ($isLoading)
                    <div class="flex flex-col items-center justify-center">
                        <x-spinner class="h-14 w-14 text-gray-800 dark:text-gray-200" />
                        <div class="my-4 text-lg font-medium text-gray-800 dark:text-gray-200">
                            {{ __('Loading log data...') }}
                        </div>
                    </div>
                @else
                    <div wire:poll.5s="refresh">
                        <x-textarea
                            id="logOutput"
                            readonly
                            wire:model.live="logOutput"
                            class="pre bg-gray-50 font-mono text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200"
                            rows="16"
                            wrap
                        ></x-textarea>
                        @if ($isStreaming)
                            <div class="mt-2 flex items-center text-sm text-gray-600 dark:text-gray-400">
                                @svg('hugeicons-refresh', 'mr-2 h-4 w-4 animate-spin')
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
