@section('title', __('Add Backup Task'))
<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Backup Task') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-form-wrapper>
                <form wire:submit.prevent="submit">

                    <!-- Steps Progress -->
                    <div class="w-full py-4">
                        @php
                            $steps = [
                                ['label' => __('Details'), 'icon' => 'heroicon-o-document-text'],
                                ['label' => __('Configuration'), 'icon' => 'heroicon-o-cog'],
                                ['label' => __('Backup Info'), 'icon' => 'heroicon-o-circle-stack'],
                                ['label' => __('Schedule'), 'icon' => 'heroicon-o-calendar'],
                                ['label' => __('Notifications'), 'icon' => 'heroicon-o-bell']
                            ];
                        @endphp

                            <!-- Mobile View -->
                        <div class="sm:hidden">
                            @foreach ($steps as $index => $step)
                                <div class="flex items-center mb-4">
                                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full
                    {{ $index < $currentStep - 1 ? 'bg-green-500' : ($index === $currentStep - 1 ? 'bg-gray-950 dark:bg-gray-50' : 'bg-gray-300 dark:bg-gray-700') }}
                    transition-all duration-300 ease-in-out">
                                        @if ($index < $currentStep - 1)
                                            @svg('heroicon-o-check-circle', 'w-5 h-5 text-white')
                                        @else
                                            @svg($step['icon'], 'w-5 h-5 ' . ($index <= $currentStep - 1 ? 'text-white dark:text-gray-950' : 'text-gray-500 dark:text-gray-200'))
                                        @endif
                                    </div>
                                    <div class="ml-4 text-sm font-medium {{ $index <= $currentStep - 1 ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-300' }}">
                                        {{ $step['label'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Desktop View -->
                        <div class="hidden sm:block">
                            <div class="flex justify-between">
                                @foreach ($steps as $index => $step)
                                    <div class="flex flex-col items-center">
                                        <div class="mb-2 flex items-center justify-center w-10 h-10 rounded-full
                        {{ $index < $currentStep - 1 ? 'bg-green-500' : ($index === $currentStep - 1 ? 'bg-gray-950 dark:bg-gray-50' : 'bg-gray-300 dark:bg-gray-700') }}
                        transition-all duration-300 ease-in-out">
                                            @if ($index < $currentStep - 1)
                                                @svg('heroicon-o-check-circle', 'w-6 h-6 text-white')
                                            @else
                                                @svg($step['icon'], 'w-6 h-6 ' . ($index <= $currentStep - 1 ? 'text-white dark:text-gray-950' : 'text-gray-500 dark:text-gray-200'))
                                            @endif
                                        </div>
                                        <div class="text-xs font-medium {{ $index <= $currentStep - 1 ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-300' }}">
                                            {{ $step['label'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4 h-2 flex">
                                @foreach ($steps as $index => $step)
                                    <div class="flex-1 {{ $index < $currentStep - 1 ? 'bg-green-500' : ($index === $currentStep - 1 ? 'bg-gray-950 dark:bg-gray-100' : 'bg-gray-300 dark:bg-gray-600') }}
                    {{ $index === 0 ? 'rounded-l-full' : '' }}
                    {{ $index === count($steps) - 1 ? 'rounded-r-full' : '' }}
                    transition-all duration-300 ease-in-out">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <!-- End Steps Progress -->

                    @if ($currentStep === 1)
                        <x-form-section>
                            {{ __('Backup Details') }}
                        </x-form-section>
                        <div class="mt-4">
                            <x-input-label for="label" :value="__('Label')"/>
                            <x-text-input id="label" class="block mt-1 w-full" type="text" wire:model="label"
                                          name="label"
                                          autofocus/>
                            <x-input-error :messages="$errors->get('label')" class="mt-2"/>
                        </div>
                        <div class="mt-4">
                            <x-input-label for="description" :value="__('Description')"/>
                            <x-textarea id="description" class="block mt-1 w-full" wire:model="description"
                                        name="description"></x-textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2"/>
                        </div>

                        @if ($availableTags->isNotEmpty())
                            <x-form-section>
                                {{ __('Tags') }}
                            </x-form-section>
                            <div class="mt-4">
                                <x-input-label for="tags" :value="__('Tags')"/>
                                @foreach ($availableTags as $tag)
                                    <x-checkbox id="tag-{{ $tag->id }}" wire:model="selectedTags" value="{{ $tag->id }}"
                                                name="tags[]" label="{{ $tag->label }}"></x-checkbox>
                                @endforeach
                                <x-input-error :messages="$errors->get('selectedTags')" class="mt-2"/>
                                <x-input-explain>
                                    {{ __('Tags are a way to categorize your backup tasks. You can use them to filter and search for tasks later.') }}
                                </x-input-explain>
                            </div>
                        @endif
                    @elseif ($currentStep === 2)
                        <x-form-section>
                            {{ __('Backup Configuration') }}
                        </x-form-section>
                        <div class="mt-4 flex space-x-6">
                            <div class="w-3/6">
                                <x-input-label for="remoteServerId" :value="__('Remote Server')"/>
                                <x-select id="remoteServerId" class="block mt-1 w-full" wire:model.live="remoteServerId"
                                          name="remoteServerId">
                                    @foreach ($remoteServers as $remoteServer)
                                        <option value="{{ $remoteServer->id }}">{{ $remoteServer->label }}
                                            ({{ $remoteServer->ip_address }})
                                        </option>
                                    @endforeach
                                </x-select>
                                <x-input-error :messages="$errors->get('remoteServerId')" class="mt-2"/>
                                <x-input-explain>
                                    {{ __('Choose the remote server from which you want to create a backup. Remember, if you plan to perform database backups on any remote server, you must set a database password for it.') }}
                                </x-input-explain>
                            </div>
                            <div class="w-3/6">
                                <x-input-label for="backupType" :value="__('Backup Type')"/>
                                <x-select id="backupType" class="block mt-1 w-full" wire:model.live="backupType"
                                          name="backupType">
                                    @foreach ($backupTypes as $type => $label)
                                        <option value="{{ $type }}">{{ ucfirst($label) }}</option>
                                    @endforeach
                                </x-select>
                                <x-input-error :messages="$errors->get('backupType')" class="mt-2"/>
                            </div>
                        </div>
                        <div class="mt-4 flex space-x-6">
                            <div class="w-3/6">
                                <x-input-label for="backupDestinationId" :value="__('Backup Destination')"/>
                                <x-select id="backupDestinationId" class="block mt-1 w-full"
                                          wire:model="backupDestinationId"
                                          name="backupDestinationId">
                                    @foreach ($backupDestinations as $backupDestination)
                                        <option value="{{ $backupDestination->id }}">{{ $backupDestination->label }}
                                            - {{ ucfirst($backupDestination->type()) }}</option>
                                    @endforeach
                                </x-select>
                                <x-input-error :messages="$errors->get('backupDestinationId')" class="mt-2"/>
                                <x-input-explain>
                                    {{ __('Choose the backup destination where you want to store the backup files. If you have not yet set up a backup destination, you can do so in the Backup Destinations section.') }}
                                </x-input-explain>
                            </div>
                            <div class="w-3/6">
                                <x-input-label for="backupsToKeep" :value="__('Maximum Backups to Keep')"/>
                                <x-text-input id="backupsToKeep" class="block mt-1 w-full" type="number"
                                              wire:model="backupsToKeep"
                                              min="0" max="50"
                                              name="backupsToKeep"/>
                                <x-input-error :messages="$errors->get('backupsToKeep')" class="mt-2"/>
                                <x-input-explain>
                                    {{ __('Set the maximum limit for stored backups. Any backups exceeding this limit will be removed, starting with the oldest. Enter 0 to disable and store all backups.') }}
                                </x-input-explain>
                            </div>
                        </div>
                    @elseif ($currentStep === 3)
                        <x-form-section>
                            {{ __('Backup Info') }}
                        </x-form-section>
                        @if ($backupType === \App\Models\BackupTask::TYPE_FILES)
                            <div class="mt-4">
                                <x-input-label for="sourcePath"
                                               :value="__('Path of Directory on Remote Server to Backup')"/>
                                <x-text-input id="sourcePath" class="block mt-1 w-full" type="text"
                                              wire:model="sourcePath"
                                              placeholder="{{ __('/path/to/backup') }}"
                                              name="sourcePath"/>
                                <x-input-error :messages="$errors->get('sourcePath')" class="mt-2"/>
                                <x-input-explain>{{ __('Please provide the UNIX path of the directory on your remote server that you would like to backup.') }}</x-input-explain>
                            </div>
                        @else
                            <div class="mt-4">
                                <x-input-label for="databaseName" :value="__('Database Name')"/>
                                <x-text-input id="databaseName" class="block mt-1 w-full" type="text"
                                              wire:model="databaseName"
                                              name="databaseName"/>
                                <x-input-error :messages="$errors->get('databaseName')" class="mt-2"/>
                                <x-input-explain>{{ __('The name of the database you want to back up.') }}</x-input-explain>
                            </div>
                            <div class="mt-4">
                                <x-input-label for="excludedDatabaseTables" :value="__('Excluded Database Tables')"/>
                                <x-text-input id="excludedDatabaseTables" class="block mt-1 w-full" type="text"
                                              wire:model="excludedDatabaseTables"
                                              placeholder="{{ __('table1,table2,table3') }}"
                                              name="excludedDatabaseTables"/>
                                <x-input-error :messages="$errors->get('excludedDatabaseTables')" class="mt-2"/>
                                <x-input-explain>{{ __('If you want to exclude certain tables from the backup, you can list them here, separated by commas. For example: "table1,table2,table3". This can be useful if you have large tables that you don\'t need to back up.') }}</x-input-explain>
                            </div>
                        @endif
                        <div class="mt-4">
                            <x-input-label for="appendedFileName" :value="__('Additional Filename Text')"/>
                            <x-text-input id="appendedFileName" class="block mt-1 w-full" type="text"
                                          wire:model="appendedFileName"
                                          placeholder="{{ __('laravel_app_db_backup') }}"
                                          name="appendedFileName"/>
                            <x-input-error :messages="$errors->get('appendedFileName')" class="mt-2"/>
                            <x-input-explain>{{ __('You have the option to add extra characters to the filename. This can make it easier for you to identify the file later.') }}</x-input-explain>
                        </div>
                        <div class="mt-4">
                            <x-input-label for="storePath" :value="__('Backup Destination Directory')"/>
                            <x-text-input id="storePath" class="block mt-1 w-full" type="text" wire:model="storePath"
                                          placeholder="{{ __('/save/to/path') }}"
                                          name="storePath"/>
                            <x-input-error :messages="$errors->get('storePath')" class="mt-2"/>
                            <x-input-explain>
                                {{ __('This is the directory path where the backup will be stored. If the specified folders do not exist, they will be automatically created. If not specified, the backup files will be placed in the root directory of your backup destination.') }}
                            </x-input-explain>
                        </div>
                        <div class="mt-4">
                            <x-input-label for="useIsolatedCredentials" :value="__('Using Isolated Environment')"/>
                            <x-toggle
                                name="useIsolatedCredentials"
                                model="useIsolatedCredentials"
                                :live="true"
                            />
                            <x-input-explain>{{ __('If you are using the isolated site functionality available on Ploi or Laravel Forge, you can provide your per site SSH details here to enable us to back it up.') }}</x-input-explain>
                        </div>
                        @if ($useIsolatedCredentials)
                            <div wire:transition>
                                <div
                                    class="mx-3 py-2 px-4 bg-amber-50 text-amber-600 border-l-4 border-amber-600 font-medium my-6">
                                    @svg('heroicon-o-exclamation-triangle', 'h-5 w-5 inline mr-2')
                                    <span>
                    {{ __('This feature is a work in progress, if there is an issue please let us know!') }}
                </span>
                                </div>
                                <div class="mt-4 flex space-x-6">
                                    <div class="w-3/6">
                                        <x-input-label for="isolatedUsername" :value="__('Username')"/>
                                        <x-text-input id="isolatedUsername" class="block mt-1 w-full" type="text"
                                                      wire:model="isolatedUsername"
                                                      name="isolatedUsername"/>
                                        <x-input-error :messages="$errors->get('isolatedUsername')" class="mt-2"/>
                                        <x-input-explain>{{ __('Please enter the username assigned to your site in Ploi or Laravel Forge.') }}</x-input-explain>
                                    </div>
                                    <div class="w-3/6">
                                        <x-input-label for="isolatedPassword" :value="__('Password')"/>
                                        <x-text-input id="isolatedPassword" class="block mt-1 w-full" type="password"
                                                      wire:model="isolatedPassword"
                                                      name="isolatedPassword"/>
                                        <x-input-error :messages="$errors->get('isolatedPassword')" class="mt-2"/>
                                        <x-input-explain>{{ __('Please provide the isolated site password generated by Ploi or Laravel Forge.') }}</x-input-explain>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @elseif ($currentStep === 4)
                        <x-form-section>
                            {{ $useCustomCron ? __('Custom Cron Expression') : __('Backup Schedule') }}
                        </x-form-section>
                        @if (!$useCustomCron)
                            <div class="mt-4 flex space-x-6">
                                <div class="w-3/4">
                                    <x-input-label for="frequency" :value="__('Backup Frequency')"/>
                                    <x-select id="frequency" class="mt-1 w-full" wire:model="frequency"
                                              name="frequency">
                                        <option value="daily">{{ __('Daily') }}</option>
                                        <option value="weekly">{{ __('Weekly') }}</option>
                                    </x-select>
                                    <x-input-error :messages="$errors->get('frequency')" class="mt-2"/>
                                </div>
                                <div class="w-1/4">
                                    <x-input-label for="timeToRun" :value="__('Time to Backup')"/>
                                    <x-select id="timeToRun" class="mt-1 w-full" wire:model="timeToRun"
                                              name="timeToRun">
                                        @foreach ($backupTimes as $backupTime)
                                            <option value="{{ $backupTime }}">{{ $backupTime }}</option>
                                        @endforeach
                                    </x-select>
                                    <x-input-error :messages="$errors->get('timeToRun')" class="mt-2"/>
                                    @if (Auth::user()->timezone)
                                        <x-input-explain>
                                            {{ __('The time is based on your timezone') }}: {{ Auth::user()->timezone }}
                                        </x-input-explain>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="mt-4">
                                <x-input-label for="cronExpression" :value="__('Cron Expression')"/>
                                <x-text-input id="cronExpression" class="block mt-1 w-full" type="text"
                                              wire:model="cronExpression" name="cronExpression" placeholder="** * **"/>
                                <x-input-error :messages="$errors->get('cronExpression')" class="mt-2"/>
                                <x-input-explain>
                                    {{ __('We recommend using a tool such as') }}
                                    <a href="https://crontab.guru" _target="_blank"
                                       class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100 ease-in-out">
                                        {{ __('Crontab Guru') }}
                                    </a>
                                    {{ __('to help you generate a valid cron expression.') }}
                                </x-input-explain>
                            </div>
                        @endif
                        <div class="mt-4">
                            <x-input-label for="useCustomCron" :value="__('Use Custom Cron')"/>
                            <x-toggle
                                name="useCustomCron"
                                model="useCustomCron"
                                :live="true"
                            />
                            <x-input-explain>{{ __('You can choose to define a cron expression for more detailed scheduling control.') }}</x-input-explain>
                        </div>
                    @elseif ($currentStep === 5)
                        <x-form-section>
                            {{ __('Notifications') }}
                        </x-form-section>
                        <div class="mt-4">
                            <x-input-label for="notifyEmail" :value="__('Email Address')"/>
                            <x-text-input id="notifyEmail" class="block mt-1 w-full" type="text"
                                          wire:model="notifyEmail"
                                          name="notifyEmail"/>
                            <x-input-error :messages="$errors->get('notifyEmail')" class="mt-2"/>
                            <x-input-explain>
                                {{ __('Input the email address to receive notifications. Regardless of this field, backup task failure notifications will always be sent to the email associated with your account.') }}
                            </x-input-explain>
                        </div>
                        <div class="mt-4 flex space-x-6">
                            <div class="w-3/6">
                                <x-input-label for="notifyDiscordWebhook" :value="__('Discord Webhook')"/>
                                <x-text-input id="notifyDiscordWebhook" class="block mt-1 w-full" type="text"
                                              wire:model="notifyDiscordWebhook"
                                              placeholder="https://discord.com/api/webhooks/..."
                                              name="notifyDiscordWebhook"/>
                                <x-input-error :messages="$errors->get('notifyDiscordWebhook')" class="mt-2"/>
                                <x-input-explain>
                                    {{ __('Input the Discord webhook to receive notifications on Discord.') }}
                                </x-input-explain>
                            </div>
                            <div class="w-3/6">
                                <x-input-label for="notifySlackWebhook" :value="__('Slack Webhook')"/>
                                <x-text-input id="notifySlackWebhook" class="block mt-1 w-full" type="text"
                                              wire:model="notifySlackWebhook"
                                              placeholder="https://hooks.slack.com/services/..."
                                              name="notifySlackWebhook"/>
                                <x-input-error :messages="$errors->get('notifySlackWebhook')" class="mt-2"/>
                                <x-input-explain>
                                    {{ __('Input the Slack webhook to receive notifications on Slack.') }}
                                </x-input-explain>
                            </div>
                        </div>
                    @endif


                    <div class="mt-6 max-w-3xl mx-auto" x-data="{
    currentStep: @entangle('currentStep'),
    totalSteps: @entangle('totalSteps'),
    handleKeydown(event) {
        if (event.key === 'Enter') {
            if (this.currentStep < this.totalSteps) {
                this.nextStep();
            } else {
                this.save();
            }
        } else if (event.key === 'ArrowLeft' && this.currentStep > 1) {
            this.previousStep();
        } else if (event.key === 'ArrowRight' && this.currentStep < this.totalSteps) {
            this.nextStep();
        }
    },
    nextStep() {
        this.$wire.nextStep();
    },
    previousStep() {
        this.$wire.previousStep();
    },
    save() {
        this.$refs.saveButton.click();
    }
}" @keydown.window="handleKeydown">
                        <div class="flex justify-center space-x-5">
                            <!-- Previous Button -->
                            <div class="w-2/6" x-show="currentStep > 1">
                                <x-secondary-button type="button" class="mt-4 w-full" centered @click="previousStep">
                                    {{ __('Previous') }}
                                </x-secondary-button>
                            </div>

                            <!-- Next/Save Button -->
                            <div :class="currentStep > 1 ? 'w-2/6' : 'w-4/6'">
                                <template x-if="currentStep < totalSteps">
                                    <x-primary-button type="button" class="mt-4 w-full" centered @click="nextStep">
                                        {{ __('Next') }}
                                    </x-primary-button>
                                </template>
                                <template x-if="currentStep >= totalSteps">
                                    <x-primary-button type="submit" class="mt-4 w-full" centered x-ref="saveButton">
                                        {{ __('Save') }}
                                    </x-primary-button>
                                </template>
                            </div>

                            <!-- Cancel Button -->
                            <div class="w-2/6">
                                <a href="{{ route('backup-tasks.index') }}" wire:navigate>
                                    <x-secondary-button type="button" class="mt-4 w-full" centered>
                                        {{ __('Cancel') }}
                                    </x-secondary-button>
                                </a>
                            </div>
                        </div>
                    </div>



                </form>
            </x-form-wrapper>
        </div>
    </div>
</div>
