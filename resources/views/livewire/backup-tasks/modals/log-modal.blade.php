<div>
    <x-modal name="backup-task-{{ $backupTask->id }}" wire:key="backup-task-{{ $backupTask->id }}">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Viewing latest log for ":label".', ['label' => $backupTask->label]) }}
            </h2>
            <p class="text-gray-800 dark:text-gray-200 my-3">
                {{ __('This is the latest log output for the backup task, there may be a slight delay when displaying logs.') }}
            </p>
            <div class="mx-3 py-2 px-4 bg-cyan-50 text-cyan-600 border-l-4 border-cyan-600 font-medium">
                @svg('heroicon-o-exclamation-triangle', 'h-5 w-5 inline mr-2')
                <span>
                    {{ __('Heads up! The log output might look a bit odd sometimes, it\'s just a visual quirk. We\'re smoothing things out.') }}
                </span>
            </div>
            <div class="my-5">
                @if ($isWaiting)
                    <div class="mx-auto" wire:transition>
                        <x-spinner class="text-gray-800 dark:text-gray-200 h-14 w-14 inline"/>
                        <div class="text-gray-800 dark:text-gray-200 font-medium my-4 text-lg">{{ __('Waiting for backup task...') }}</div>
                    </div>
                @else
                    <div wire:transition>
                        <x-textarea id="logOutput" readonly class="pre text-sm text-gray-800 bg-gray-50 font-mono" rows="16" wrap>
                            {{ $logOutput }}
                        </x-textarea>
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
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            window.setInterval(function() {
                const elem = document.getElementById('logOutput');
                if (elem) {
                    elem.scrollTop = elem.scrollHeight;
                }
            }, 500);
        });
    </script>
</div>
