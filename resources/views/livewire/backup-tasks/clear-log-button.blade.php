<div>
    @if (Auth::user()->backupTaskLogCount() > 0)
        <div class="my-3 flex justify-end">
            <a href="#" class="text-sm text-red-600 dark:text-red-400 font-medium hover:text-underline ease-in-out"
               x-data=""
               x-on:click.prevent="$dispatch('open-modal', 'clear-all-backup-task-logs')">
                @svg('heroicon-o-x-mark', 'h-5 w-5 inline-block -mt-0.5')
                {{ __('Clear All Backup Task Logs') }}
            </a>
        </div>
        <x-modal name="clear-all-backup-task-logs">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('Clear All Backup Task Logs') }}
                </h2>
                <p class="text-gray-800 dark:text-gray-200 my-3">
                    {{ __('Are you sure you want to clear your backup task log history?') }}
                </p>
                <p class="text-gray-800 dark:text-gray-200 my-3">
                    {{ __('All your backups will still exist at their backup destination but there will be no record of them within :app. Please confirm your request.', ['app' => config('app.name')]) }}
                </p>
                <div class="flex space-x-5">
                    <div class="w-4/6">
                        <x-danger-button type="button" wire:click="clearAllLogs" class="mt-4" centered
                                         wire:loading.attr="disabled"
                                         wire:loading.class="opacity-50 cursor-not-allowed">

                            <div wire:loading wire:target="clearAllLogs">
                                <x-spinner class="mr-2 text-white h-4 w-4 inline"/>
                                {{ __('Clearing...') }}
                            </div>

                            <div wire:loading.remove wire:target="clearAllLogs">
                                {{ __('Confirm') }}
                            </div>
                        </x-danger-button>
                    </div>
                    <div class="w-2/6 ml-2">
                        <x-secondary-button type="button" class="mt-4" centered x-on:click="$dispatch('close')">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                    </div>
                </div>
            </div>
        </x-modal>
    @endif
</div>

