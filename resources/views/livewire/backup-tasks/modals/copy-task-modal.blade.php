<div>
    <x-modal name="copy-backup-task" focusable>
        <x-slot name="title">
            {{ __('Copy Backup Task') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Create a new backup task by replicating an existing configuration.') }}
        </x-slot>
        <x-slot name="icon">
            hugeicons-clipboard
        </x-slot>
        <form wire:submit.prevent="copyTask">
            <div>
                <div>
                    <x-input-label for="backupTaskToCopyId" :value="__('Backup Task')" />
                    <x-select
                        id="backupTaskToCopyId"
                        name="backupTaskToCopyId"
                        class="mt-1 block w-full"
                        wire:model.live="backupTaskToCopyId">
                        <option value="">{{ __('Select a backup task') }}</option>
                        @foreach ($backupTasks as $task)
                            <option value="{{ $task->id }}">{{ $task->label }} ({{ $task->remoteServer->label }})</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('backupTaskToCopyId')" class="mt-2" />
                    <x-input-explain>
                        {{ __('Please choose the backup task you wish to copy.') }}
                    </x-input-explain>
                </div>
                <div class="mt-4">
                    <x-input-label for="optionalNewLabel" :value="__('Label (Optional)')" />
                    <x-text-input
                        id="optionalNewLabel"
                        name="optionalNewLabel"
                        type="text"
                        class="mt-1 block w-full"
                        wire:model="optionalNewLabel"
                    />
                    <x-input-error :messages="$errors->get('optionalNewLabel')" class="mt-2" />
                    <x-input-explain>
                        {{ __('Optionally, you may choose a new label for the copied backup task.') }}
                    </x-input-explain>
                </div>
                <div class="mt-4">
                    <div class="mt-2 flex space-x-4">
                        <div class="w-1/2">
                            <x-input-label for="frequency" :value="__('Frequency')" />
                            <x-select
                                id="frequency"
                                name="frequency"
                                class="mt-1 block w-full"
                                wire:model="frequency"
                            >
                                <option value="daily">{{ __('Daily') }}</option>
                                <option value="weekly">{{ __('Weekly') }}</option>
                            </x-select>
                            <x-input-error :messages="$errors->get('frequency')" class="mt-2" />
                        </div>
                        <div class="w-1/2">
                            <x-input-label for="timeToRun" :value="__('Time to Run')" />
                            <x-select
                                id="timeToRun"
                                name="timeToRun"
                                class="mt-1 block w-full"
                                wire:model="timeToRun"
                            >
                                @foreach ($backupTimes as $time)
                                    <option value="{{ $time }}">{{ $time }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error :messages="$errors->get('timeToRun')" class="mt-2" />
                        </div>
                    </div>
                    <x-input-explain>
                        {{ __('Select a non-conflicting schedule for this task. Each server allows only one task per time slot. For advanced scheduling using Cron, please edit the task after copying.') }}
                    </x-input-explain>
                </div>
                <div class="flex space-x-5">
                    <div class="w-4/6">
                        <x-primary-button type="button" class="mt-4" centered wire:click="copyTask" action="copyTask" loadingText="Copying...">
                            {{ __('Copy') }}
                        </x-primary-button>
                    </div>
                    <div class="w-2/6 ml-2">
                        <x-secondary-button type="button" class="mt-4" centered x-on:click="$dispatch('close-modal', 'copy-backup-task')">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                    </div>
                </div>
            </div>
        </form>
    </x-modal>
</div>
