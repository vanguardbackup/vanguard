<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Forms;

use App\Models\BackupTask;
use App\Models\NotificationStream;
use App\Models\RemoteServer;
use App\Models\Tag;
use App\Rules\UniqueScheduledTimePerRemoteServer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Toaster;

class CreateBackupTaskForm extends Component
{
    public string $label = '';

    public ?string $description = '';

    public ?string $sourcePath = null;

    public ?int $remoteServerId = null;

    public string $backupDestinationId = '';

    public ?string $frequency = BackupTask::FREQUENCY_DAILY;

    public ?string $timeToRun = '00:00';

    public bool $useCustomCron = false;

    public ?string $cronExpression = null;

    public int $backupsToKeep = 5;

    public string $backupType = BackupTask::TYPE_FILES;

    public ?string $databaseName = null;

    public ?string $appendedFileName = null;

    public string $userTimezone;

    public ?string $storePath = null;

    public ?string $excludedDatabaseTables = null;

    public bool $useIsolatedCredentials = false;

    public ?string $isolatedUsername = null;

    public ?string $isolatedPassword = null;

    public int $currentStep = 1;

    public int $totalSteps = 5;

    /** @var Collection<int, RemoteServer>|null */
    public ?Collection $remoteServers = null;

    /** @var \Illuminate\Support\Collection<int, string> */
    public \Illuminate\Support\Collection $backupTimes;

    /** @var Collection<int, Tag>|null */
    public ?Collection $availableTags = null;

    /** @var array<int>|null */
    public ?array $selectedTags = [];

    /** @var Collection<int, NotificationStream>|null */
    public ?Collection $availableNotificationStreams = null;

    /** @var array<int>|null */
    public ?array $selectedStreams = [];

    /** @var array<string, string> */
    protected array $validationAttributes = [
        'label' => 'Label',
        'description' => 'Description',
        'remoteServerId' => 'Remote Server',
        'backupType' => 'Backup Type',
        'backupDestinationId' => 'Backup Destination',
        'backupsToKeep' => 'Maximum Backups to Keep',
        'sourcePath' => 'Path of Directory on Remote Server to Backup',
        'databaseName' => 'Database Name',
        'excludedDatabaseTables' => 'Excluded Database Tables',
        'appendedFileName' => 'Additional Filename Text',
        'storePath' => 'Backup Destination Directory',
        'frequency' => 'Backup Frequency',
        'timeToRun' => 'Time to Backup',
        'cronExpression' => 'Cron Expression',
    ];

    public function mount(): void
    {
        $this->initializeDefaultValues();
        $this->initializeBackupTimes();
        $this->updatedBackupType();
        $this->updatedUseCustomCron();
    }

    public function nextStep(): void
    {
        $this->validate($this->getStepRules(), $this->messages());
        $this->currentStep = min($this->currentStep + 1, $this->totalSteps);
    }

    public function previousStep(): void
    {
        $this->currentStep = max($this->currentStep - 1, 1);
    }

    public function updatedUseCustomCron(): void
    {
        if ($this->useCustomCron) {
            $this->timeToRun = null;
            $this->frequency = null;
        } else {
            $this->cronExpression = null;
        }
    }

    public function updatedUseIsolatedCredentials(): void
    {
        if (! $this->useIsolatedCredentials) {
            $this->isolatedUsername = null;
            $this->isolatedPassword = null;
        }
    }

    public function updatedBackupType(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->remoteServers = $this->backupType === BackupTask::TYPE_FILES
            ? $user->remoteServers
            : $user->remoteServers->where('database_password', '!=', null);

        $this->remoteServerId = $this->remoteServers->first()?->id;
    }

    public function submit(): RedirectResponse|Redirector
    {
        $this->validate($this->rules(), $this->messages());
        $this->processScheduleSettings();
        $backupTask = BackupTask::create($this->prepareBackupTaskData());

        /** @var Tag $tags */
        $tags = $this->selectedTags;

        $backupTask->tags()->sync($tags);

        /** @var NotificationStream $notificationStreams */
        $notificationStreams = $this->selectedStreams;

        $backupTask->notificationStreams()->sync($notificationStreams);

        Toaster::success(__('Backup task has been added.'));

        return Redirect::route('backup-tasks.index');
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.forms.create-backup-task-form', [
            'backupTimes' => $this->backupTimes,
            'backupDestinations' => Auth::user()?->backupDestinations ?? collect(),
            'backupTypes' => [
                BackupTask::TYPE_FILES => __('files'),
                BackupTask::TYPE_DATABASE => __('database'),
            ],
            'remoteServers' => $this->remoteServers,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $baseRules = [
            'isolatedUsername' => ['nullable', 'string'],
            'isolatedPassword' => ['nullable', 'string'],
            'selectedStreams' => ['nullable', 'array', Rule::exists('notification_streams', 'id')->where('user_id', Auth::id())],
            'selectedTags' => ['nullable', 'array', Rule::exists('tags', 'id')->where('user_id', Auth::id())],
            'excludedDatabaseTables' => ['nullable', 'string', 'regex:/^([a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)*)$/'],
            'storePath' => ['nullable', 'string', 'regex:/^(\/[^\/\0]+)+\/?$/'],
            'appendedFileName' => ['nullable', 'string', 'max:40', 'alpha_dash'],
            'backupType' => ['required', 'string', 'in:files,database'],
            'backupsToKeep' => ['required', 'integer', 'min:0', 'max:50'],
            'label' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:100'],
            'databaseName' => ['nullable', 'string', 'required_if:backupType,database'],
            'remoteServerId' => ['required', 'int', 'exists:remote_servers,id'],
            'backupDestinationId' => ['required', 'string', 'exists:backup_destinations,id'],
            'frequency' => ['required', 'string', 'in:daily,weekly'],
            'timeToRun' => [
                'string',
                'regex:/^([01]?\d|2[0-3]):([0-5]?\d)$/',
                'required_unless:useCustomCron,true',
            ],
            'cronExpression' => [
                'nullable',
                'string',
                'regex:/^(\*|([0-5]?\d)) (\*|([01]?\d|2[0-3])) (\*|([0-2]?\d|3[01])) (\*|([1-9]|1[0-2])) (\*|([0-7]))$/',
                'required_if:useCustomCron,true',
            ],
        ];

        if ($this->backupType === BackupTask::TYPE_FILES) {
            $baseRules['sourcePath'] = ['required', 'string', 'regex:/^(\/[^\/\0]+)+\/?$/'];
        }

        if ($this->remoteServerId !== null) {
            $baseRules['timeToRun'][] = new UniqueScheduledTimePerRemoteServer($this->remoteServerId);
        }

        return $baseRules;
    }

    public function updatedRemoteServerId(?int $value): void
    {

        if ($this->remoteServerId !== null && $this->timeToRun !== null) {
            $conflictingTask = BackupTask::where('remote_server_id', $this->remoteServerId)
                ->where('time_to_run_at', $this->timeToRun)
                ->first();

            if ($conflictingTask) {
                $this->addError('timeToRun', 'The scheduled time for this remote server is already taken. Please choose a different time.');
            } else {
                $this->resetErrorBag('timeToRun');
            }
        }
    }

    private function initializeDefaultValues(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->useIsolatedCredentials = false;
        $this->availableTags = $user->tags;
        $this->availableNotificationStreams = $user->notificationStreams;
        $this->userTimezone = $user->timezone ?? 'UTC';
        $this->remoteServers = $user->remoteServers->where('database_password', null);
        $this->remoteServerId = $this->remoteServers->first()?->id;
        $this->backupDestinationId = (string) ($user->preferred_backup_destination_id ?? $user->backupDestinations->first()?->id ?? '');
    }

    private function initializeBackupTimes(): void
    {
        $this->backupTimes = collect(range(0, 95))->map(fn ($quarterHour): string => sprintf('%02d:%02d', intdiv($quarterHour, 4), ($quarterHour % 4) * 15)
        );
    }

    private function processScheduleSettings(): void
    {
        if ($this->cronExpression) {
            $this->timeToRun = null;
            $this->frequency = null;
        } elseif ($this->timeToRun && $this->frequency) {
            $this->cronExpression = null;
        }

        if ($this->userTimezone === 'UTC') {
            return;
        }

        if (! $this->timeToRun) {
            return;
        }

        $this->timeToRun = Carbon::createFromFormat('H:i', $this->timeToRun, $this->userTimezone)?->setTimezone('UTC')->format('H:i');
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'selectedTags.*.exists' => __('One or more of the selected tags do not exist.'),
            'storePath.regex' => __('The path must be a valid Unix path.'),
            'appendedFileName.max' => __('The appended file name must be less than 40 characters.'),
            'appendedFileName.alpha_dash' => __('The appended file name may only contain letters, numbers, dashes, and underscores.'),
            'databaseName.required_if' => __('Please enter the name of the database.'),
            'backupType.required' => __('Please choose a backup type.'),
            'backupType.in' => __('The backup type must be either "files" or "database".'),
            'backupsToKeep.required' => __('Please enter the number of backups to keep.'),
            'backupsToKeep.integer' => __('The number of backups to keep must be an integer.'),
            'backupsToKeep.min' => __('You cannot enter a number lower than 0.'),
            'backupsToKeep.max' => __('You cannot store more than 50 backups.'),
            'label.required' => __('Please enter a label for the backup task.'),
            'description.max' => __('Your description must be less than 100 characters.'),
            'sourcePath.required' => __('Please enter the source path for the backup task.'),
            'remoteServerId.required' => __('Please choose a remote server.'),
            'backupDestinationId.required' => __('Please choose a backup destination.'),
            'frequency.required' => __('Please choose a frequency for the backup task.'),
            'timeToRun.required_unless' => __('Please select a time to run the backup task.'),
            'cronExpression.required_if' => __('Please enter a cron expression for the backup task.'),
            'sourcePath.regex' => __('The path must be a valid Unix path.'),
            'timeToRun.regex' => __('You have entered an invalid time. Please enter a time in the format HH:MM.'),
            'cronExpression.regex' => __('You have entered an invalid cron expression.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareBackupTaskData(): array
    {
        return [
            'user_id' => Auth::id(),
            'remote_server_id' => $this->remoteServerId,
            'backup_destination_id' => $this->backupDestinationId,
            'label' => $this->label,
            'description' => $this->description ?: '',
            'source_path' => $this->sourcePath,
            'frequency' => $this->frequency,
            'time_to_run_at' => $this->timeToRun,
            'custom_cron_expression' => $this->cronExpression,
            'status' => 'ready',
            'maximum_backups_to_keep' => $this->backupsToKeep,
            'type' => $this->backupType,
            'database_name' => $this->databaseName,
            'appended_file_name' => $this->appendedFileName,
            'store_path' => $this->storePath,
            'excluded_database_tables' => $this->excludedDatabaseTables,
            'isolated_username' => $this->isolatedUsername,
            'isolated_password' => $this->isolatedPassword ? Crypt::encryptString($this->isolatedPassword) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getStepRules(): array
    {
        $allRules = $this->rules();

        return match ($this->currentStep) {
            1 => array_intersect_key($allRules, array_flip(['label', 'description', 'selectedTags'])),
            2 => array_intersect_key($allRules, array_flip(['remoteServerId', 'backupType', 'backupDestinationId', 'backupsToKeep'])),
            3 => array_intersect_key($allRules, array_flip(['sourcePath', 'databaseName', 'excludedDatabaseTables', 'appendedFileName', 'storePath'])),
            4 => array_intersect_key($allRules, array_flip(['frequency', 'timeToRun', 'cronExpression'])),
            5 => array_intersect_key($allRules, array_flip(['selectedNotificationStreams'])),
            default => [],
        };
    }
}
