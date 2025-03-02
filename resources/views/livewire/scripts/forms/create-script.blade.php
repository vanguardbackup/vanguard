{{-- blade-formatter-disable --}}
@php
    use App\Models\Script;
@endphp

<div>
    <x-form-wrapper>
        <x-slot name="title">
            {{ __('Create a Script') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Attach a new script to your account for use in your backup tasks!') }}
        </x-slot>
        <x-slot name="icon">hugeicons-computer</x-slot>

        <form wire:submit="submit">
            <div class="mt-4">
                <x-input-label for="label" :value="__('Label')" />
                <x-text-input
                    id="label"
                    class="mt-1 block w-full"
                    type="text"
                    wire:model="label"
                    name="label"
                    autofocus
                />
                <x-input-error :messages="$errors->get('label')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="type" :value="__('Script Type')" />
                <x-select id="type" class="mt-1 w-full" wire:model.live="type" name="type">
                    <option value="{{ Script::TYPE_PRESCRIPT }}">{{ __('Pre-backup Script') }}</option>
                    <option value="{{ Script::TYPE_POSTSCRIPT }}">{{ __('Post-backup Script') }}</option>
                </x-select>
                <x-input-explain>
                    {{ __('Please choose whether the script should run before or after the backup task.') }}
                </x-input-explain>
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>

            <!-- This would replace the script content section in your Livewire form -->
            <div class="mt-4">
                <x-input-label for="script" :value="__('Script Content')" />
                <x-textarea
                    id="script"
                    class="mt-1 block w-full font-mono"
                    wire:model="script"
                    name="script"
                    rows="10"
                    placeholder="#!/bin/bash
# Your script code here
# Example:
echo 'Backup starting...'"
                />
                <x-input-error :messages="$errors->get('script')" class="mt-2" />
            </div>

            <div class="my-2">
                <x-notice type="warning" title="{{ __('Important: Script Execution Guidelines') }}">
                    <p class="mb-2">{{ __('Avoid long-running commands in these scripts as they may:') }}</p>
                    <ul class="mb-2 ml-5 list-disc">
                        <li>{{ __('Time out during execution') }}</li>
                        <li>{{ __('Block the backup process') }}</li>
                        <li>{{ __('Cause connection interruptions') }}</li>
                    </ul>
                </x-notice>
            </div>

            <div class="mt-4">
                <x-input-label :value="__('Assign to Backup Tasks')" />
                <div class="mt-2 max-h-60 overflow-y-auto rounded-md border border-gray-300 p-3 dark:border-gray-600">
                    @forelse ($this->backupTasks as $task)
                        <div class="flex items-center py-1">
                            <div class="flex-1">
                                <x-checkbox
                                    id="task-{{ $task->id }}"
                                    wire:model="selectedTasks.{{ $task->id }}"
                                    value="{{ $task->id }}"
                                    name="tasks[]"
                                    label="{{ $task->label }}"
                                    class="{{ isset($existingAssociations[$task->id][$type]) ? 'text-amber-500' : '' }}"
                                ></x-checkbox>

                                @if (isset($existingAssociations[$task->id][$type]))
                                    <div class="ml-7 text-sm text-amber-600">
                                        {{
                                            __('Warning: Will replace existing ":type" script: :label', [
                                                'type' => $type === Script::TYPE_PRESCRIPT ? __('Pre-backup') : __('Post-backup'),
                                                'label' => $existingAssociations[$task->id][$type]['label'],
                                            ])
                                        }}
                                    </div>
                                @endif

                                @if (isset($existingAssociations[$task->id][Script::TYPE_PRESCRIPT]) && $type !== Script::TYPE_PRESCRIPT)
                                    <div class="ml-7 text-sm text-gray-500">
                                        {{
                                            __('Has Pre-backup script: :label', [
                                                'label' => $existingAssociations[$task->id][Script::TYPE_PRESCRIPT]['label'],
                                            ])
                                        }}
                                    </div>
                                @endif

                                @if (isset($existingAssociations[$task->id][Script::TYPE_POSTSCRIPT]) && $type !== Script::TYPE_POSTSCRIPT)
                                    <div class="ml-7 text-sm text-gray-500">
                                        {{
                                            __('Has Post-backup script: :label', [
                                                'label' => $existingAssociations[$task->id][Script::TYPE_POSTSCRIPT]['label'],
                                            ])
                                        }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('No backup tasks available to assign') }}</p>
                    @endforelse
                </div>
                <x-input-error :messages="$errors->get('selectedTasks')" class="mt-2" />
                <x-input-explain>
                    {{ __('A backup task can only have one pre-backup or post-backup script assigned at a time. Selecting a task with an existing script will replace that script.') }}
                </x-input-explain>
            </div>

            <div class="mx-auto mt-6 max-w-3xl">
                <div class="flex flex-col space-y-4 sm:flex-row sm:space-x-5 sm:space-y-0">
                    <div class="w-full sm:w-4/6">
                        <x-primary-button type="submit" class="w-full justify-center" centered action="submit">
                            {{ __('Save') }}
                        </x-primary-button>
                    </div>
                    <div class="w-full sm:w-2/6">
                        <a href="{{ route('scripts.index') }}" wire:navigate class="block">
                            <x-secondary-button type="button" class="w-full justify-center" centered>
                                {{ __('Cancel') }}
                            </x-secondary-button>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </x-form-wrapper>
</div>
