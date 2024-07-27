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

/**
 * Class CreateBackupTaskForm
 *
 * This Livewire component handles the creation of a new backup task.
 * It manages a multistep form process, including validation and submission.
 */
class CreateBackupTaskForm extends Component
{
    public string $label = '';

    public string $description = '';

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

    public int $totalSteps = 6;

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

    public bool $showCronPresets = false;
    public string $cronPresetSearch = '';
    /**
     * @var array<string, string>
     */
    public array $cronPresets = [];

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

    /**
     * Open the cron presets modal.
     */
    public function openCronPresets(): void
    {
        $this->showCronPresets = true;
    }

    /**
     * Close the cron presets modal.
     */
    public function closeCronPresets(): void
    {
        $this->showCronPresets = false;
    }

    /**
     * Set the cron expression from a preset.
     */
    public function setPreset(string $preset): void
    {
        $this->cronExpression = $preset;
        $this->dispatch('close-modal', 'cron-presets');
        $this->cronPresetSearch = '';
    }

    /**
     * Get filtered and grouped cron presets based on the current search term.
     *
     * @return array<string, array<string, string>>
     */
    public function getFilteredCronPresets(): array
    {
        $groupedPresets = [
            $this->ensureString(__('Daily Backups')) => [],
            $this->ensureString(__('Weekly Backups')) => [],
            $this->ensureString(__('Monthly Backups')) => [],
            $this->ensureString(__('Custom Intervals')) => [],
            $this->ensureString(__('Business Hours')) => [],
        ];

        foreach ($this->cronPresets as $expression => $description) {
            if (! $this->matchesSearch($description)) {
                continue;
            }

            $translatedDescription = $this->ensureString(__($description));
            $group = $this->determinePresetGroup($translatedDescription);
            $groupedPresets[$group][$expression] = $translatedDescription;
        }

        return $groupedPresets;
    }

    /**
     * Initialize the component state.
     *
     * This method is called when the component is first loaded.
     * It sets up default values and initial state for the form.
     */
    public function mount(): void
    {
        $this->initializeDefaultValues();
        $this->initializeBackupTimes();
        $this->updatedBackupType();
        $this->updatedUseCustomCron();
        $this->initializeCronPresets();
    }

    /**
     * Move to the next step in the form process.
     *
     * This method validates the current step before proceeding to the next one.
     */
    public function nextStep(): void
    {
        $this->validate($this->getStepRules(), $this->messages());
        $this->currentStep = min($this->currentStep + 1, $this->totalSteps);
    }

    /**
     * Move to the previous step in the form process.
     */
    public function previousStep(): void
    {
        $this->currentStep = max($this->currentStep - 1, 1);
    }

    /**
     * Handle changes to the custom cron toggle.
     *
     * This method updates the form state when the user toggles between
     * custom cron and standard scheduling options.
     */
    public function updatedUseCustomCron(): void
    {
        if ($this->useCustomCron) {
            $this->timeToRun = null;
            $this->frequency = null;
        } else {
            $this->cronExpression = null;
        }
    }

    /**
     * Handle changes to the isolated credentials toggle.
     *
     * This method clears isolated credential fields when toggled off.
     */
    public function updatedUseIsolatedCredentials(): void
    {
        if (! $this->useIsolatedCredentials) {
            $this->isolatedUsername = null;
            $this->isolatedPassword = null;
        }
    }

    /**
     * Handle changes to the backup type.
     *
     * This method updates the available remote servers based on the selected backup type.
     */
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

    /**
     * Submit the form and create a new backup task.
     *
     * This method validates the entire form, creates the backup task,
     * and redirects to the backup tasks index page.
     */
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

        Toaster::success('Backup task has been added.');

        return Redirect::route('backup-tasks.index');
    }

    /**
     * Render the component.
     */
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
     * Define the validation rules for the form.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $cronRegex = '/^(\*|(\*\/)?([0-5]?[0-9])([-,]([0-5]?[0-9]))*)' // minute
            . '\s+(\*|(\*\/)?([0-1]?[0-9]|2[0-3])([-,]([0-1]?[0-9]|2[0-3]))*)' // hour
            . '\s+(\*|(\*\/)?([1-2]?[0-9]|3[0-1])([-,]([1-2]?[0-9]|3[0-1]))*)' // day of month
            . '\s+(\*|(\*\/)?([1-9]|1[0-2])([-,]([1-9]|1[0-2]))*)' // month
            . '\s+(\*|(\*\/)?([0-7])([-,]([0-7]))*)'  // day of week
            . '$/';

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
                'regex:' . $cronRegex,
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

    /**
     * Handle changes to the remote server selection.
     *
     * This method checks for conflicting backup tasks when a new remote server is selected.
     */
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

    /**
     * Get the summary of the backup task configuration.
     *
     * @return array<string, string>
     */
    public function getSummary(): array
    {
        $remoteServer = RemoteServer::find($this->remoteServerId);
        $backupDestination = Auth::user()?->backupDestinations->firstWhere('id', $this->backupDestinationId);

        $summary = [
            'Label' => $this->label,
            'Description' => $this->description ?: 'Not Set',
            'Remote Server' => $remoteServer ? "{$remoteServer->label} ({$remoteServer->ip_address})" : 'Not Set',
            'Backup Type' => ucfirst($this->backupType),
            'Backup Destination' => $backupDestination
                ? "{$backupDestination->label} - " . ucfirst((string) $backupDestination->type())
                : 'Not Set',
            'Maximum Backups to Keep' => (string) $this->backupsToKeep,
            'Source Path' => $this->sourcePath ?? 'Not Set',
            'Database Name' => $this->databaseName ?? 'Not Set',
            'Excluded Database Tables' => $this->excludedDatabaseTables ?? 'None',
            'Additional Filename Text' => $this->appendedFileName ?? 'Not Set',
            'Backup Destination Directory' => $this->storePath ?? 'Root directory of backup destination',
            'Schedule' => $this->useCustomCron
                ? "Custom: {$this->cronExpression}"
                : ucfirst((string) $this->frequency) . " at {$this->timeToRun}",
            'Using Isolated Environment' => $this->useIsolatedCredentials ? 'Yes' : 'No',
            'Tags' => $this->getSelectedTagLabels(),
            'Notification Streams' => $this->getSelectedStreamLabels(),
        ];

        // Translate all keys and values
        return array_combine(
            array_map(fn ($key) => __($key), array_keys($summary)),
            array_map(fn ($value) => __($value), $summary)
        );
    }

    /**
     * Retrieves the first input field on each step.
     */
    public function getFirstInputId(): ?string
    {
        return match ($this->currentStep) {
            1 => 'label',
            2 => 'remoteServerId',
            3 => $this->backupType === BackupTask::TYPE_FILES ? 'sourcePath' : 'databaseName',
            4 => $this->useCustomCron ? 'cronExpression' : 'frequency',
            5 => $this->availableNotificationStreams instanceof Collection && $this->availableNotificationStreams->isNotEmpty()
                ? 'stream-' . $this->availableNotificationStreams->first()->getAttribute('id')
                : null,
            default => null,
        };
    }

    /**
     * Ensure the given value is a string.
     *
     * @param  string|array<string, string>  $value
     */
    private function ensureString($value): string
    {
        if (is_array($value)) {
            return implode(', ', $value);
        }

        return $value;
    }

    /**
     * Determine the group for a given cron preset description.
     */
    private function determinePresetGroup(string $description): string
    {
        $lowercaseDescription = strtolower($description);

        if (str_contains($lowercaseDescription, strtolower($this->ensureString(__('Every day'))))) {
            return $this->ensureString(__('Daily Backups'));
        }

        if (str_contains($lowercaseDescription, strtolower($this->ensureString(__('Every week'))))) {
            return $this->ensureString(__('Weekly Backups'));
        }

        if (str_contains($lowercaseDescription, strtolower($this->ensureString(__('Every month')))) ||
            str_contains($lowercaseDescription, strtolower($this->ensureString(__('Every 3 months'))))) {
            return $this->ensureString(__('Monthly Backups'));
        }

        if (str_contains($lowercaseDescription, strtolower($this->ensureString(__('Monday to Friday')))) ||
            str_contains($lowercaseDescription, strtolower($this->ensureString(__('weekday'))))) {
            return $this->ensureString(__('Business Hours'));
        }

        return $this->ensureString(__('Custom Intervals'));
    }

    /**
     * Check if the description matches the current search term.
     */
    private function matchesSearch(string $description): bool
    {
        if ($this->cronPresetSearch === '' || $this->cronPresetSearch === '0') {
            return true;
        }

        return str_contains(strtolower($description), strtolower($this->cronPresetSearch));
    }

    /**
     * Initialize the cron presets with their translated descriptions.
     */
    private function initializeCronPresets(): void
    {
        $this->cronPresets = [
            // Daily backups
            '0 0 * * *' => __('Every day at midnight'),
            '0 2 * * *' => __('Every day at 2 AM'),
            '0 4 * * *' => __('Every day at 4 AM'),
            '0 1 * * *' => __('Every day at 1 AM (off-peak hours)'),
            '0 23 * * *' => __('Every day at 11 PM'),

            // Multiple times per day
            '0 */6 * * *' => __('Every 6 hours'),
            '0 */12 * * *' => __('Every 12 hours'),
            '0 */4 * * *' => __('Every 4 hours'),
            '0 */8 * * *' => __('Every 8 hours'),

            // Specific days of the week
            '0 0 * * 5' => __('Every Friday at midnight'),
            '0 0 * * 1' => __('Every Monday at midnight'),
            '0 2 * * 6' => __('Every Saturday at 2 AM'),

            // Multiple days per week
            '0 3 * * 1,4' => __('Every Monday and Thursday at 3 AM'),
            '0 2 * * 2,5' => __('Every Tuesday and Friday at 2 AM'),

            // Weekly backups
            '0 0 * * 0' => __('Every week on Sunday at midnight'),
            '0 1 * * 1' => __('Every week on Monday at 1 AM'),

            // Monthly backups
            '0 0 1 * *' => __('Every month on the 1st at midnight'),
            '0 2 1 * *' => __('Every month on the 1st at 2 AM'),
            '0 3 15 * *' => __('Every month on the 15th at 3 AM'),

            // Quarterly backups
            '0 0 1 */3 *' => __('Every 3 months on the 1st at midnight'),

            // Yearly backup
            '0 0 1 1 *' => __('Every year on Jan 1st at midnight'),

            // Less frequent backups
            '0 0 */3 * *' => __('Every 3 days at midnight'),
            '0 0 */7 * *' => __('Every 7 days at midnight'),
            '0 1 */5 * *' => __('Every 5 days at 1 AM'),

            // Business hours
            '0 9-17 * * 1-5' => __('Every hour from 9 AM to 5 PM, Monday to Friday'),
            '0 8,18 * * 1-5' => __('Twice daily at 8 AM and 6 PM, Monday to Friday'),

            // End of business day
            '0 18 * * 1-5' => __('Every weekday at 6 PM'),

            // First and last day of the month
            '0 1 1,L * *' => __('On the first and last day of every month at 1 AM'),

            // Every weekend
            '0 2 * * 6,0' => __('Every Saturday and Sunday at 2 AM'),
        ];
    }

    /**
     * Initialize default values for the form.
     */
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

    /**
     * Initialize the backup times collection.
     */
    private function initializeBackupTimes(): void
    {
        $this->backupTimes = collect(range(0, 95))->map(fn ($quarterHour): string => sprintf('%02d:%02d', intdiv($quarterHour, 4), ($quarterHour % 4) * 15)
        );
    }

    /**
     * Process the schedule settings before saving.
     *
     * This method handles timezone conversions and sets the appropriate schedule fields.
     */
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
     * Get the validation error messages.
     *
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
     * Prepare the data for creating a new backup task.
     *
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
     * Get the validation rules for the current step.
     *
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
            5 => array_intersect_key($allRules, array_flip(['selectedStreams'])),
            6 => [], // Summary step, no validation needed
            default => [],
        };
    }

    /**
     * Get the labels of selected tags.
     */
    private function getSelectedTagLabels(): string
    {
        if ($this->selectedTags === null || $this->selectedTags === [] || ! $this->availableTags instanceof Collection) {
            return 'None';
        }

        $selectedTags = $this->availableTags->whereIn('id', $this->selectedTags);

        return $selectedTags->isNotEmpty()
            ? $selectedTags->pluck('label')->implode(', ')
            : 'None';
    }

    /**
     * Get the labels of selected notification streams.
     */
    private function getSelectedStreamLabels(): string
    {
        if ($this->selectedStreams === null || $this->selectedStreams === [] || ! $this->availableNotificationStreams instanceof Collection) {
            return __('None');
        }

        $selectedStreams = $this->availableNotificationStreams->whereIn('id', $this->selectedStreams);

        return $selectedStreams->isNotEmpty()
            ? $selectedStreams->pluck('label')->implode(', ')
            : __('None');
    }
}
