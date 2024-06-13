<div>
    <div class="my-3 flex justify-end">
        <a href="#" class="text-sm text-red-600 dark:text-red-400 font-medium hover:text-underline ease-in-out" x-data=""
           x-on:click.prevent="$dispatch('open-modal', 'remove-backup-task-{{ $backupTask->id }}')">
            @svg('heroicon-o-x-mark', 'h-5 w-5 inline-block -mt-0.5')
            {{ __('Remove Backup Task') }}
        </a>
    </div>
    <x-modal name="remove-backup-task-{{ $backupTask->id }}">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Remove Backup Task') }}
            </h2>
            <p class="text-gray-800 dark:text-gray-200 my-3">
                {{ __('Are you sure you want to remove the backup task ":label"?', ['label' => $backupTask->label]) }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 my-3">
                {{ __('This action cannot be undone. All your backups will still exist but no more backups will be created.') }}
            </p>
            <div class="flex space-x-5">
                <div class="w-4/6">
                    <x-danger-button type="button" wire:click="delete" class="mt-4" centered wire:loading.attr="disabled"
                                     wire:loading.class="opacity-50 cursor-not-allowed">

                        <div wire:loading wire:target="delete">
                            <x-spinner class="mr-2 text-white h-4 w-4 inline"/>
                            {{ __('Removing...') }}
                        </div>

                        <div wire:loading.remove wire:target="delete">
                            {{ __('Confirm Removal') }}
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
</div>
