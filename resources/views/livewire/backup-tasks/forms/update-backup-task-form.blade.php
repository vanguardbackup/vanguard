<div>
    <x-form-wrapper>
        <x-slot name="title">
            {{ __('Update Backup Task') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Update your backup task.') }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-archive-box
        </x-slot>
        <form wire:submit.prevent="submit">
            <x-form-section>
                {{ __('Backup Task Details') }}
            </x-form-section>
            <div class="mt-4">
                <x-input-label for="label" :value="__('Label')"/>
                <x-text-input id="label" class="block mt-1 w-full" type="text" wire:model="label" name="label"
                              autofocus/>
                <x-input-error :messages="$errors->get('label')" class="mt-2"/>
            </div>
            <div class="mt-4">
                <x-input-label for="description" :value="__('Description')"/>
                <x-textarea id="description" class="block mt-1 w-full" wire:model="description"
                            name="description"></x-textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2"/>
            </div>
            <x-form-section>
                {{ __('Backup Configuration') }}
            </x-form-section>
            <div class="mt-4 flex flex-col sm:flex-row sm:space-x-6 space-y-4 sm:space-y-0">
                <div class="w-full sm:w-3/6">
                    <x-input-label for="remoteServerId" :value="__('Remote Server')"/>
                    <x-select id="remoteServerId" class="block mt-1 w-full" wire:model.live="remoteServerId" name="remoteServerId">
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
                <div class="w-full sm:w-3/6">
                    <x-input-label for="backupType" :value="__('Backup Type')"/>
                    <x-select id="backupType" class="block mt-1 w-full" wire:model.live="backupType" name="backupType">
                        @foreach ($backupTypes as $type => $label)
                            <option value="{{ $type }}">{{ ucfirst($label) }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('backupType')" class="mt-2"/>
                </div>
            </div>
            <div class="mt-4 flex flex-col sm:flex-row sm:space-x-6 space-y-4 sm:space-y-0">
                <div class="w-full sm:w-3/6">
                    <x-input-label for="backupDestinationId" :value="__('Backup Destination')"/>
                    <x-select id="backupDestinationId" class="block mt-1 w-full" wire:model.live="backupDestinationId" name="backupDestinationId">
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
                <div class="w-full sm:w-3/6">
                    <x-input-label for="backupsToKeep" :value="__('Maximum Backups to Keep')"/>
                    <x-text-input id="backupsToKeep" class="block mt-1 w-full" type="number" wire:model="backupsToKeep"
                                  min="0" max="50"
                                  name="backupsToKeep"/>
                    <x-input-error :messages="$errors->get('backupsToKeep')" class="mt-2"/>
                    <x-input-explain>
                        {{ __('Set the maximum limit for stored backups. Any backups exceeding this limit will be removed, starting with the oldest. Enter 0 to disable and store all backups.') }}
                    </x-input-explain>
                </div>
            </div>
            @if ($backupType === \App\Models\BackupTask::TYPE_FILES)
                <div class="mt-4">
                    <x-input-label for="sourcePath" :value="__('Path of Directory on Remote Server to Backup')"/>
                    <x-text-input id="sourcePath" class="block mt-1 w-full" type="text" wire:model="sourcePath"
                                  placeholder="/path/to/backup"
                                  name="sourcePath"/>
                    <x-input-error :messages="$errors->get('sourcePath')" class="mt-2"/>
                    <x-input-explain>{{ __('Please provide the UNIX path of the directory on your remote server that you would like to backup.') }}</x-input-explain>
                </div>
            @else
                <div class="mt-4">
                    <x-input-label for="databaseName" :value="__('Database Name')"/>
                    <x-text-input id="databaseName" class="block mt-1 w-full" type="text" wire:model="databaseName"
                                  name="databaseName"/>
                    <x-input-error :messages="$errors->get('databaseName')" class="mt-2"/>
                    <x-input-explain>{{ __('The name of the database you want to back up.') }}</x-input-explain>
                </div>
                <div class="mt-4">
                    <x-input-label for="excludedDatabaseTables" :value="__('Excluded Database Tables')"/>
                    <x-text-input id="excludedDatabaseTables" class="block mt-1 w-full" type="text"
                                  wire:model="excludedDatabaseTables" placeholder="table1,table2,table3"
                                  name="excludedDatabaseTables"/>
                    <x-input-error :messages="$errors->get('excludedDatabaseTables')" class="mt-2"/>
                    <x-input-explain>{{ __('If you want to exclude certain tables from the backup, you can list them here, separated by commas. This can be useful if you have large tables that you don\'t need to back up.') }}</x-input-explain>
                </div>
            @endif
            <div class="mt-4">
                <x-input-label for="encryptionPassword" :value="__('Encryption Password')"/>
                <x-text-input id="encryptionPassword" class="block mt-1 w-full" type="password"
                              wire:model="encryptionPassword"
                              name="encryptionPassword"/>
                <x-input-error :messages="$errors->get('encryptionPassword')" class="mt-2"/>
                <x-input-explain>{{ __('You can optionally set an encryption password which will enhance the security of this backup. If you have an encrypted password already, you can change it by updating this field.') }}</x-input-explain>
            </div>
            <div class="mt-4">
                <x-input-label for="appendedFileName" :value="__('Additional Filename Text')"/>
                <x-text-input id="appendedFileName" class="block mt-1 w-full" type="text" wire:model="appendedFileName"
                              name="appendedFileName"/>
                <x-input-error :messages="$errors->get('appendedFileName')" class="mt-2"/>
                <x-input-explain>{{ __('You have the option to add extra characters to the filename. This can make it easier for you to identify the file later.') }}</x-input-explain>
            </div>
            <div class="mt-4">
                <x-input-label for="storePath" :value="__('Backup Destination Directory')"/>
                <x-text-input id="storePath" class="block mt-1 w-full" type="text" wire:model="storePath"
                              placeholder="/save/to/path"
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
                <div class="mt-4 flex flex-col sm:flex-row sm:space-x-6 space-y-4 sm:space-y-0">
                    <div class="w-full sm:w-3/6">
                        <x-input-label for="isolatedUsername" :value="__('Username')"/>
                        <x-text-input id="isolatedUsername" class="block mt-1 w-full" type="text" wire:model="isolatedUsername"
                                      name="isolatedUsername"/>
                        <x-input-error :messages="$errors->get('isolatedUsername')" class="mt-2"/>
                        <x-input-explain>{{ __('Please provide the isolated site username generated by Ploi or Laravel Forge.') }}</x-input-explain>
                    </div>
                    <div class="w-full sm:w-3/6">
                        <x-input-label for="isolatedPassword" :value="__('Password')"/>
                        <x-text-input id="isolatedPassword" class="block mt-1 w-full" type="password" wire:model="isolatedPassword"
                                      name="isolatedPassword"/>
                        <x-input-error :messages="$errors->get('isolatedPassword')" class="mt-2"/>
                        <x-input-explain>{{ __('To provide a new password please enter it here. By default it will use your stored isolated password, if set already.') }}</x-input-explain>
                    </div>
                </div>
            @endif
            <x-form-section>
                {{ $useCustomCron ? __('Custom Cron Expression') : __('Backup Schedule') }}
            </x-form-section>
            @if (!$useCustomCron)
                <div class="mt-4 flex flex-col sm:flex-row sm:space-x-6 space-y-4 sm:space-y-0">
                    <div class="w-full sm:w-3/4">
                        <x-input-label for="frequency" :value="__('Backup Frequency')"/>
                        <x-select id="frequency" class="mt-1 w-full" wire:model="frequency" name="frequency">
                            <option value="daily">{{ __('Daily') }}</option>
                            <option value="weekly">{{ __('Weekly') }}</option>
                        </x-select>
                        <x-input-error :messages="$errors->get('frequency')" class="mt-2"/>
                    </div>
                    <div class="w-full sm:w-1/4">
                        <x-input-label for="timeToRun" :value="__('Time to Backup')"/>
                        <x-select id="timeToRun" class="mt-1 w-full" wire:model="timeToRun" name="timeToRun">
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
            <x-form-section>
                {{ __('Notifications') }}
            </x-form-section>
            @if ($availableNotificationStreams->isNotEmpty())
                <div class="mt-4">
                    <x-input-label for="notificationStreams" :value="__('Notifications')"/>
                    @foreach ($availableNotificationStreams as $notificationStream)
                        <x-checkbox id="stream-{{ $notificationStream->id }}" wire:model="selectedStreams" value="{{ $notificationStream->id }}"
                                    name="streams[]" label="{{ $notificationStream->label . '(' . $notificationStream->formatted_type . ')' }}"></x-checkbox>
                    @endforeach
                    <x-input-error :messages="$errors->get('selectedTags')" class="mt-2"/>
                    <x-input-explain>
                        {{ __('Notifications about this backup task will be sent on the notification streams you choose.') }}
                    </x-input-explain>
                </div>
            @else
                {{ __('You do not have any notification streams configured. You will by default receive backup task failure notifications to your accounts email address.') }}
            @endif

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
            <div class="mt-6 max-w-3xl mx-auto">
                <div class="flex flex-col sm:flex-row sm:space-x-5 space-y-4 sm:space-y-0">
                    <div class="w-full sm:w-4/6">
                        <x-primary-button type="submit" class="w-full justify-center" centered action="submit"
                                          loadingText="Saving changes...">
                            {{ __('Save changes') }}
                        </x-primary-button>
                    </div>
                    <div class="w-full sm:w-2/6">
                        <a href="{{ route('backup-tasks.index') }}" wire:navigate class="block">
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
