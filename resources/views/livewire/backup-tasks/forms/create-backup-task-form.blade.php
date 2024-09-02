@section('title', __('Add Backup Task'))
<div>
    <x-slot name="header">
        {{ __('Add Backup Task') }}
    </x-slot>
    <div>
        <div>
            <x-form-wrapper>
                <x-slot name="title">
                    {{ __('Add Backup Task') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('Create a new backup task.') }}
                </x-slot>
                <x-slot name="icon">hugeicons-archive-02</x-slot>
                <form wire:submit.prevent="submit">
                    <!-- Steps Progress -->
                    <div class="w-full px-4 py-6 sm:px-6 md:px-8">
                        @php
                            $steps = [
                                ['label' => __('Details'), 'icon' => 'hugeicons-profile'],
                                ['label' => __('Configuration'), 'icon' => 'hugeicons-settings-02'],
                                ['label' => __('Backup Info'), 'icon' => 'hugeicons-database'],
                                ['label' => __('Schedule'), 'icon' => 'hugeicons-calendar-01'],
                                ['label' => __('Notifications'), 'icon' => 'hugeicons-notification-02'],
                                ['label' => __('Summary'), 'icon' => 'hugeicons-profile-02'],
                            ];
                        @endphp

                        <!-- Mobile View -->
                        <div class="space-y-4 sm:hidden">
                            @foreach ($steps as $index => $step)
                                <div class="flex items-center">
                                    <div
                                        class="{{ $index < $currentStep - 1 ? 'bg-green-500' : ($index === $currentStep - 1 ? 'bg-gray-950 dark:bg-gray-50' : 'bg-gray-300 dark:bg-gray-700') }} flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full transition-all duration-300 ease-in-out"
                                    >
                                        @if ($index < $currentStep - 1)
                                            @svg('hugeicons-checkmark-circle-02', 'h-5 w-5 text-white')
                                        @else
                                            @svg(
                                                $step['icon'],
                                                'w-5 h-5 ' .
                                                ($index <= $currentStep - 1
                                                    ? 'text-white
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                dark:text-gray-950'
                                                    : 'text-gray-500 dark:text-gray-200')
                                            )
                                        @endif
                                    </div>
                                    <div
                                        class="{{ $index <= $currentStep - 1 ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-300' }} ml-3 text-sm font-medium"
                                    >
                                        {{ $step['label'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Desktop View -->
                        <div class="hidden sm:block">
                            <div class="flex items-center justify-between">
                                @foreach ($steps as $index => $step)
                                    <div class="group relative flex flex-col items-center">
                                        <div
                                            class="{{ $index < $currentStep - 1 ? 'bg-green-500' : ($index === $currentStep - 1 ? 'bg-gray-950 dark:bg-gray-50' : 'bg-gray-300 dark:bg-gray-700') }} mb-2 flex h-11 w-11 transform items-center justify-center rounded-full transition-all duration-300 ease-in-out hover:scale-105"
                                        >
                                            @if ($index < $currentStep - 1)
                                                @svg('hugeicons-checkmark-circle-02', 'h-6 w-6 text-white')
                                            @else
                                                @svg(
                                                    $step['icon'],
                                                    'w-6 h-6 ' . ($index <= $currentStep - 1 ? 'text-white dark:text-gray-950' : 'text-gray-500 dark:text-gray-200')
                                                )
                                            @endif
                                        </div>
                                        <div
                                            class="{{ $index <= $currentStep - 1 ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-300' }} text-center text-xs font-medium"
                                        >
                                            {{ $step['label'] }}
                                        </div>
                                        <div
                                            class="absolute -bottom-6 left-1/2 -translate-x-1/2 transform whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                        >
                                            Step {{ $index + 1 }}
                                        </div>
                                    </div>
                                    @if ($index < count($steps) - 1)
                                        <div class="mx-2 h-0.5 flex-1">
                                            <div
                                                class="{{ $index < $currentStep - 1 ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }} h-full rounded-full transition-all duration-500 ease-in-out"
                                                style="width: {{ $index < $currentStep - 1 ? '100%' : '0%' }}"
                                            ></div>
                                        </div>
                                    @endif
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
                            <x-input-label for="description" :value="__('Description')" />
                            <x-textarea
                                id="description"
                                class="mt-1 block w-full"
                                wire:model="description"
                                name="description"
                            ></x-textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        @if ($availableTags->isNotEmpty())
                            <x-form-section>
                                {{ __('Tags') }}
                            </x-form-section>
                            <div class="mt-4">
                                <x-input-label for="tags" :value="__('Tags')" />
                                @foreach ($availableTags as $tag)
                                    <x-checkbox
                                        id="tag-{{ $tag->id }}"
                                        wire:model="selectedTags"
                                        value="{{ $tag->id }}"
                                        name="tags[]"
                                        label="{{ $tag->label }}"
                                    ></x-checkbox>
                                @endforeach

                                <x-input-error :messages="$errors->get('selectedTags')" class="mt-2" />
                                <x-input-explain>
                                    {{ __('Tags are a way to categorize your backup tasks. You can use them to filter and search for tasks later.') }}
                                </x-input-explain>
                            </div>
                        @endif
                    @elseif ($currentStep === 2)
                        <x-form-section>
                            {{ __('Backup Configuration') }}
                        </x-form-section>
                        <div class="mt-4 flex flex-col space-y-4 sm:flex-row sm:space-x-6 sm:space-y-0">
                            <div class="w-full sm:w-3/6">
                                <x-input-label for="remoteServerId" :value="__('Remote Server')" />
                                <x-select
                                    id="remoteServerId"
                                    class="mt-1 block w-full"
                                    wire:model.live="remoteServerId"
                                    name="remoteServerId"
                                >
                                    @foreach ($remoteServers as $remoteServer)
                                        <option value="{{ $remoteServer->id }}">
                                            {{ $remoteServer->label }}
                                            ({{ $remoteServer->ip_address }})
                                        </option>
                                    @endforeach
                                </x-select>
                                <x-input-error :messages="$errors->get('remoteServerId')" class="mt-2" />
                                <x-input-explain>
                                    {{ __('Choose the remote server from which you want to create a backup. Remember, if you plan to perform database backups on any remote server, you must set a database password for it.') }}
                                </x-input-explain>
                            </div>
                            <div class="w-full sm:w-3/6">
                                <x-input-label for="backupType" :value="__('Backup Type')" />
                                <x-select
                                    id="backupType"
                                    class="mt-1 block w-full"
                                    wire:model.live="backupType"
                                    name="backupType"
                                >
                                    @foreach ($backupTypes as $type => $label)
                                        <option value="{{ $type }}">
                                            {{ ucfirst($label) }}
                                        </option>
                                    @endforeach
                                </x-select>
                                <x-input-error :messages="$errors->get('backupType')" class="mt-2" />
                            </div>
                        </div>
                        <div class="mt-4 flex flex-col space-y-4 sm:flex-row sm:space-x-6 sm:space-y-0">
                            <div class="w-full sm:w-3/6">
                                <x-input-label for="backupDestinationId" :value="__('Backup Destination')" />
                                <x-select
                                    id="backupDestinationId"
                                    class="mt-1 block w-full"
                                    wire:model="backupDestinationId"
                                    name="backupDestinationId"
                                >
                                    @foreach ($backupDestinations as $backupDestination)
                                        <option value="{{ $backupDestination->id }}">
                                            {{ $backupDestination->label }} -
                                            {{ ucfirst($backupDestination->type()) }}
                                        </option>
                                    @endforeach
                                </x-select>
                                <x-input-error :messages="$errors->get('backupDestinationId')" class="mt-2" />
                                <x-input-explain>
                                    {{ __('Choose the backup destination where you want to store the backup files. If you have not yet set up a backup destination, you can do so in the Backup Destinations section.') }}
                                </x-input-explain>
                            </div>
                            <div class="w-full sm:w-3/6">
                                <x-input-label for="backupsToKeep" :value="__('Maximum Backups to Keep')" />
                                <x-text-input
                                    id="backupsToKeep"
                                    class="mt-1 block w-full"
                                    type="number"
                                    wire:model="backupsToKeep"
                                    min="0"
                                    max="50"
                                    name="backupsToKeep"
                                />
                                <x-input-error :messages="$errors->get('backupsToKeep')" class="mt-2" />
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
                                <x-input-label
                                    for="sourcePath"
                                    :value="__('Path of Directory on Remote Server to Backup')"
                                />
                                <x-text-input
                                    id="sourcePath"
                                    class="mt-1 block w-full"
                                    type="text"
                                    wire:model="sourcePath"
                                    placeholder="/path/to/backup"
                                    name="sourcePath"
                                />
                                <x-input-error :messages="$errors->get('sourcePath')" class="mt-2" />
                                <x-input-explain>
                                    {{ __('Please provide the UNIX path of the directory on your remote server that you would like to backup.') }}
                                </x-input-explain>
                            </div>
                        @else
                            <div class="mt-4">
                                <x-input-label for="databaseName" :value="__('Database Name')" />
                                <x-text-input
                                    id="databaseName"
                                    class="mt-1 block w-full"
                                    type="text"
                                    wire:model="databaseName"
                                    name="databaseName"
                                />
                                <x-input-error :messages="$errors->get('databaseName')" class="mt-2" />
                                <x-input-explain>
                                    {{ __('The name of the database you want to back up.') }}
                                </x-input-explain>
                            </div>
                            <div class="mt-4">
                                <x-input-label for="excludedDatabaseTables" :value="__('Excluded Database Tables')" />
                                <x-text-input
                                    id="excludedDatabaseTables"
                                    class="mt-1 block w-full"
                                    type="text"
                                    wire:model="excludedDatabaseTables"
                                    placeholder="table1,table2,table3"
                                    name="excludedDatabaseTables"
                                />
                                <x-input-error :messages="$errors->get('excludedDatabaseTables')" class="mt-2" />
                                <x-input-explain>
                                    {{ __('If you want to exclude certain tables from the backup, you can list them here, separated by commas. This can be useful if you have large tables that you don\'t need to back up.') }}
                                </x-input-explain>
                            </div>
                        @endif
                        <div class="mt-4">
                            <x-input-label for="encryptionPassword" :value="__('Encryption Password')" />
                            <x-text-input
                                id="encryptionPassword"
                                class="mt-1 block w-full"
                                type="password"
                                wire:model="encryptionPassword"
                                name="encryptionPassword"
                            />
                            <x-input-error :messages="$errors->get('encryptionPassword')" class="mt-2" />
                            <x-input-explain>
                                {{ __('You can optionally set an encryption password which will enhance the security of this backup.') }}
                            </x-input-explain>
                        </div>
                        <div class="mt-4">
                            <x-input-label for="appendedFileName" :value="__('Additional Filename Text')" />
                            <x-text-input
                                id="appendedFileName"
                                class="mt-1 block w-full"
                                type="text"
                                wire:model="appendedFileName"
                                name="appendedFileName"
                            />
                            <x-input-error :messages="$errors->get('appendedFileName')" class="mt-2" />
                            <x-input-explain>
                                {{ __('You have the option to add extra characters to the filename. This can make it easier for you to identify the file later.') }}
                            </x-input-explain>
                        </div>
                        <div class="mt-4">
                            <x-input-label for="storePath" :value="__('Backup Destination Directory')" />
                            <x-text-input
                                id="storePath"
                                class="mt-1 block w-full"
                                type="text"
                                wire:model="storePath"
                                placeholder="/save/to/path"
                                name="storePath"
                            />
                            <x-input-error :messages="$errors->get('storePath')" class="mt-2" />
                            <x-input-explain>
                                {{ __('This is the directory path where the backup will be stored. If the specified folders do not exist, they will be automatically created. If not specified, the backup files will be placed in the root directory of your backup destination.') }}
                            </x-input-explain>
                        </div>
                    @elseif ($currentStep === 4)
                        <x-form-section>
                            {{ $useCustomCron ? __('Custom Cron Expression') : __('Backup Schedule') }}
                        </x-form-section>
                        @if (! $useCustomCron)
                            <div class="mt-4 flex flex-col space-y-4 sm:flex-row sm:space-x-6 sm:space-y-0">
                                <div class="w-full sm:w-3/4">
                                    <x-input-label for="frequency" :value="__('Backup Frequency')" />
                                    <x-select
                                        id="frequency"
                                        class="mt-1 w-full"
                                        wire:model="frequency"
                                        name="frequency"
                                    >
                                        <option value="daily">
                                            {{ __('Daily') }}
                                        </option>
                                        <option value="weekly">
                                            {{ __('Weekly') }}
                                        </option>
                                    </x-select>
                                    <x-input-error :messages="$errors->get('frequency')" class="mt-2" />
                                </div>
                                <div class="w-full sm:w-1/4">
                                    <x-input-label for="timeToRun" :value="__('Time to Backup')" />
                                    <x-select
                                        id="timeToRun"
                                        class="mt-1 w-full"
                                        wire:model="timeToRun"
                                        name="timeToRun"
                                    >
                                        @foreach ($backupTimes as $backupTime)
                                            <option value="{{ $backupTime }}">
                                                {{ $backupTime }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                    <x-input-error :messages="$errors->get('timeToRun')" class="mt-2" />
                                    @if (Auth::user()->timezone)
                                        <x-input-explain>
                                            {{ __('The time is based on your timezone') }}:
                                            {{ Auth::user()->timezone }}
                                        </x-input-explain>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="mt-4">
                                <x-input-label for="cronExpression" :value="__('Cron Expression')" />
                                <div class="mt-1 flex items-center">
                                    <x-text-input
                                        id="cronExpression"
                                        class="block w-full"
                                        type="text"
                                        wire:model="cronExpression"
                                        name="cronExpression"
                                        placeholder="* * * * *"
                                    />
                                    <x-secondary-button
                                        type="button"
                                        class="ml-2"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'cron-presets')"
                                    >
                                        @svg('hugeicons-clock-01', 'mr-1 h-5 w-5')
                                        {{ __('Presets') }}
                                    </x-secondary-button>
                                </div>
                                <x-input-error :messages="$errors->get('cronExpression')" class="mt-2" />
                                <x-input-explain>
                                    {{ __('We recommend using a tool such as') }}
                                    <a
                                        href="https://crontab.guru"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-sm text-gray-600 underline ease-in-out hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                                    >
                                        Crontab Guru
                                    </a>
                                    {{ __('to help you generate a valid cron expression.') }}
                                </x-input-explain>
                            </div>
                        @endif
                        <div class="mt-4">
                            <x-input-label for="useCustomCron" :value="__('Use Custom Cron')" />
                            <x-toggle name="useCustomCron" model="useCustomCron" :live="true" />
                            <x-input-explain>
                                {{ __('You can choose to define a cron expression for more detailed scheduling control.') }}
                            </x-input-explain>
                        </div>
                    @elseif ($currentStep === 5)
                        <x-form-section>
                            {{ __('Notifications') }}
                        </x-form-section>
                        @if ($availableNotificationStreams->isNotEmpty())
                            <div class="mt-4">
                                <x-input-label for="notificationStreams" :value="__('Notifications')" />
                                @foreach ($availableNotificationStreams as $notificationStream)
                                    <x-checkbox
                                        id="stream-{{ $notificationStream->id }}"
                                        wire:model="selectedStreams"
                                        value="{{ $notificationStream->id }}"
                                        name="streams[]"
                                        label="{{ $notificationStream->label . '(' . $notificationStream->formatted_type . ')' }}"
                                    ></x-checkbox>
                                @endforeach

                                <x-input-error :messages="$errors->get('$selectedStreams')" class="mt-2" />
                                <x-input-explain>
                                    {{ __('Notifications about this backup task will be sent on the notification streams you choose.') }}
                                </x-input-explain>
                            </div>
                        @else
                            {{ __('You do not have any notification streams configured. You will by default receive backup task failure notifications to your accounts email address.') }}
                        @endif
                    @elseif ($currentStep === 6)
                        <x-form-section>
                            {{ __('Backup Task Summary') }}
                        </x-form-section>
                        <div class="mt-4">
                            <div class="overflow-hidden bg-white sm:rounded-lg dark:bg-gray-800">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3
                                        class="flex items-center text-lg font-medium leading-6 text-gray-900 dark:text-gray-100"
                                    >
                                        @svg('hugeicons-profile-02', 'mr-2 h-6 w-6 text-green-500')
                                        {{ __('Review Your Backup Task') }}
                                    </h3>
                                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Please review the details of your backup task before saving.') }}
                                    </p>
                                </div>
                                <div class="px-4 py-5 sm:px-6 dark:border-gray-700">
                                    <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($this->getSummary() as $key => $value)
                                            <div
                                                x-data="{ hover: false }"
                                                @mouseenter="hover = true"
                                                @mouseleave="hover = false"
                                                class="relative rounded-lg bg-gray-50 p-4 transition-all duration-300 dark:bg-gray-700"
                                                :class="{ 'shadow-md transform scale-105': hover }"
                                            >
                                                <dt
                                                    class="mb-1 flex items-center text-sm font-medium text-gray-500 dark:text-gray-300"
                                                >
                                                    @switch($key)
                                                        @case(__('Label'))
                                                            @svg('hugeicons-tag-01', 'mr-2 h-5 w-5 text-blue-500')

                                                            @break
                                                        @case(__('Description'))
                                                            @svg(
                                                                'hugeicons-clipboard',
                                                                'w-5 h-5 mr-2
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                text-purple-500'
                                                            )

                                                            @break
                                                        @case(__('Remote Server'))
                                                            @svg('hugeicons-cloud-server', 'mr-2 h-5 w-5 text-green-500')

                                                            @break
                                                        @case(__('Backup Type'))
                                                            @svg(
                                                                'hugeicons-blockchain-01',
                                                                'w-5 h-5 mr-2
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                text-yellow-500'
                                                            )

                                                            @break
                                                        @case(__('Supplied Encryption Password'))
                                                            @svg(
                                                                'hugeicons-square-lock-02',
                                                                'w-5 h-5 mr-2
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                text-yellow-500'
                                                            )

                                                            @break
                                                        @case(__('Backup Destination'))
                                                            @svg('hugeicons-folder-02', 'mr-2 h-5 w-5 text-indigo-500')

                                                            @break
                                                        @case(__('Maximum Backups to Keep'))
                                                            @svg(
                                                                'hugeicons-compass',
                                                                'w-5 h-5 mr-2
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                text-red-500'
                                                            )

                                                            @break
                                                        @case(__('Source Path'))
                                                            @svg('hugeicons-folder-01', 'mr-2 h-5 w-5 text-orange-500')

                                                            @break
                                                        @case(__('Database Name'))
                                                            @svg(
                                                                'hugeicons-database',
                                                                'w-5 h-5 mr-2
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                text-cyan-500'
                                                            )

                                                            @break
                                                        @case(__('Schedule'))
                                                            @svg('hugeicons-calendar-01', 'mr-2 h-5 w-5 text-pink-500')

                                                            @break
                                                        @case(__('Tags'))
                                                            @svg('hugeicons-tags', 'mr-2 h-5 w-5 text-lime-500')

                                                            @break
                                                        @case(__('Notification Streams'))
                                                            @svg('hugeicons-notification-02', 'mr-2 h-5 w-5 text-amber-500')

                                                            @break
                                                        @default
                                                            @svg(
                                                                'hugeicons-information-circle',
                                                                'w-5 h-5 mr-2
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                text-gray-500'
                                                            )
                                                    @endswitch
                                                    {{ $key }}
                                                </dt>
                                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                    @if (strlen($value) > 50)
                                                        <span x-data="{ expanded: false }">
                                                            <span x-show="!expanded">
                                                                {{ Str::limit($value, 50) }}
                                                            </span>
                                                            <span x-show="expanded">
                                                                {{ $value }}
                                                            </span>
                                                            <button
                                                                @click="expanded = !expanded"
                                                                class="ml-1 text-blue-500 hover:underline focus:outline-none"
                                                            >
                                                                <span x-show="!expanded">
                                                                    {{ __('Show more') }}
                                                                </span>
                                                                <span x-show="expanded">
                                                                    {{ __('Show less') }}
                                                                </span>
                                                            </button>
                                                        </span>
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div
                        class="mx-auto mt-6 max-w-3xl"
                        x-data="{
                            currentStep: @entangle('currentStep'),
                            totalSteps: @entangle('totalSteps'),
                            focusFirstInput() {
                                this.$nextTick(() => {
                                    const firstInputId = this.$wire.getFirstInputId()
                                    if (firstInputId) {
                                        const firstInput = document.getElementById(firstInputId)
                                        if (firstInput) {
                                            firstInput.focus()
                                        }
                                    }
                                })
                            },
                            handleKeydown(event) {
                                if (event.key === 'Enter') {
                                    if (this.currentStep < this.totalSteps) {
                                        this.nextStep()
                                    } else {
                                        this.save()
                                    }
                                } else if (event.key === 'ArrowLeft' && this.currentStep > 1) {
                                    this.previousStep()
                                } else if (
                                    event.key === 'ArrowRight' &&
                                    this.currentStep < this.totalSteps
                                ) {
                                    this.nextStep()
                                }
                            },
                            nextStep() {
                                this.$wire.nextStep().then(() => this.focusFirstInput())
                            },
                            previousStep() {
                                this.$wire.previousStep().then(() => this.focusFirstInput())
                            },
                            save() {
                                this.$refs.saveButton.click()
                            },
                        }"
                        x-init="focusFirstInput"
                        @keydown.window="handleKeydown"
                    >
                        <div class="flex flex-col justify-center space-y-4 sm:flex-row sm:space-x-5 sm:space-y-0">
                            <!-- Previous Button -->
                            <div class="w-full sm:w-2/6" x-show="currentStep > 1">
                                <x-secondary-button
                                    type="button"
                                    class="w-full justify-center"
                                    centered
                                    @click="previousStep"
                                >
                                    {{ __('Previous') }}
                                </x-secondary-button>
                            </div>

                            <!-- Next/Save Button -->
                            <div class="w-full" :class="currentStep > 1 ? 'sm:w-2/6' : 'sm:w-4/6'">
                                <template x-if="currentStep < totalSteps">
                                    <x-primary-button
                                        type="button"
                                        class="w-full justify-center"
                                        centered
                                        @click="nextStep"
                                    >
                                        {{ __('Next') }}
                                    </x-primary-button>
                                </template>
                                <template x-if="currentStep >= totalSteps">
                                    <x-primary-button
                                        type="submit"
                                        class="w-full justify-center"
                                        centered
                                        x-ref="saveButton"
                                    >
                                        {{ __('Save') }}
                                    </x-primary-button>
                                </template>
                            </div>

                            <!-- Cancel Button -->
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
    </div>

    <!-- Cron Presets Modal -->
    <x-modal name="cron-presets" focusable>
        <x-slot name="title">
            {{ __('Common Cron Job Presets') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Select a preset to quickly set up common backup schedules. The cron expression will be automatically filled in for you.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-calendar-02</x-slot>
        <div class="p-6">
            <div class="mb-4">
                <x-input-label for="cronPresetSearch" :value="__('Search Presets')" />
                <div class="relative">
                    @svg('hugeicons-search-01', 'absolute left-3 top-3 h-5 w-5 text-gray-400')
                    <x-text-input
                        name="cronPresetSearch"
                        id="cronPresetSearch"
                        type="text"
                        class="mt-1 block w-full pl-10"
                        wire:model.live="cronPresetSearch"
                        :placeholder="__('Type to search...')"
                    />
                </div>
            </div>
            <div class="max-h-[50vh] space-y-6 overflow-y-auto pr-2">
                @forelse ($this->getFilteredCronPresets() as $group => $presets)
                    <div>
                        <h3 class="text-md mb-2 font-medium text-gray-700 dark:text-gray-300">
                            {{ $group }}
                        </h3>
                        <div class="space-y-3">
                            @foreach ($presets as $expression => $description)
                                <div
                                    class="flex flex-col rounded-lg bg-gray-50 p-3 sm:flex-row sm:items-center sm:justify-between dark:bg-gray-800"
                                >
                                    <div class="mb-2 flex-grow sm:mb-0">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ $description }}
                                        </span>
                                        <div
                                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                            x-data="{ showTooltip: false }"
                                        >
                                            <span
                                                @mouseenter="showTooltip = true"
                                                @mouseleave="showTooltip = false"
                                                class="cursor-help"
                                            >
                                                {{ $expression }}
                                            </span>
                                            <div
                                                x-show="showTooltip"
                                                class="absolute z-10 mt-1 rounded bg-black p-2 text-xs text-white"
                                            >
                                                {{ __('Cron Expression') }}
                                            </div>
                                        </div>
                                    </div>
                                    <x-primary-button
                                        type="button"
                                        wire:click="setPreset('{{ $expression }}')"
                                        class="w-full justify-center sm:w-auto"
                                    >
                                        {{ __('Use') }}
                                    </x-primary-button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No matching presets found.') }}
                    </p>
                @endforelse
            </div>
        </div>
    </x-modal>
</div>
