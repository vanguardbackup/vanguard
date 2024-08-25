<div>
    <x-modal name="backup-task-remove-historic-log-{{ $backupTaskLog->id }}">
        <x-slot name="title">
            {{ __('Clear Backup Task Log - :label', ['label' => $backupTaskLog->backupTask->label]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('Please read this carefully before confirming this action.') }}
        </x-slot>
        <x-slot name="icon">
            hugeicons-delete-02
        </x-slot>
        <div>
            <p class="text-gray-800 dark:text-gray-200 mb-3">
                {{ __('Are you sure you want to clear this log?') }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 my-3">
                {{ __('The backup data will still exist at your backup destination, however there will be no record of this within :app.', ['app' => config('app.name')]) }}
            </p>
            <div class="flex space-x-5">
                <div class="w-4/6">
                    <x-danger-button type="button" class="mt-4" centered wire:click="delete" action="delete" loadingText="Clearing...">
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
</div>
