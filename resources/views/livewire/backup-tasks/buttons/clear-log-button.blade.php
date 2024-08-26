<div>
    @if (Auth::user()->backupTaskLogCount() > 0)
        <div class="my-3 flex justify-end">
            <a href="#" class="text-sm text-red-600 dark:text-red-400 font-medium hover:text-underline ease-in-out"
               x-data=""
               x-on:click.prevent="$dispatch('open-modal', 'clear-all-backup-task-logs')">
                @svg('hugeicons-delete-02', 'h-5 w-5 inline-block -mt-0.5')
                {{ __('Clear All Backup Task Logs') }}
            </a>
        </div>
        <x-modal name="clear-all-backup-task-logs">
            <x-slot name="title">
                {{ __('Clear All Backup Task Logs') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Please read this carefully before confirming this action.') }}
            </x-slot>
            <x-slot name="icon">
                hugeicons-delete-02
            </x-slot>
            <div>
                <p class="mb-3">
                    {{ __('Are you sure you want to clear your backup task log history?') }}
                </p>
                <p class="my-3">
                    {{ __('All your backups will still exist at their backup destination but there will be no record of them within :app. Please confirm your request.', ['app' => config('app.name')]) }}
                </p>
                <div class="flex space-x-5">
                    <div class="w-4/6">
                        <x-danger-button type="button" class="mt-4" centered wire:click="clearAllLogs" action="clearAllLogs" loadingText="Clearing...">
                            {{ __('Confirm') }}
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

