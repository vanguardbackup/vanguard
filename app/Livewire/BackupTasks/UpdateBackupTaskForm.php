<?php

namespace App\Livewire\BackupTasks;

use App\Models\BackupTask;
use App\Rules\UniqueScheduledTimePerRemoteServer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Toaster;

class UpdateBackupTaskForm extends Component
{
    public string $label = '';

    public ?string $description = null;

    public ?string $sourcePath = null;

    public string $remoteServerId = '';

    public string $backupDestinationId = '';

    public ?string $frequency = BackupTask::FREQUENCY_DAILY;

    public ?string $timeToRun = '00:00';

    public ?string $cronExpression = null;

    public int $backupsToKeep = 5;

    public string $backupType = BackupTask::TYPE_FILES;

    public ?Collection $remoteServers;

    public ?string $databaseName = null;

    public ?string $appendedFileName = null;

    public BackupTask $backupTask;

    public bool $useCustomCron = false;

    public ?string $notifyEmail = null;

    public ?string $notifyDiscordWebhook = null;

    public ?string $notifySlackWebhook = null;

    public \Illuminate\Support\Collection $backupTimes;

    public string $userTimezone;

    public ?string $storePath;

    public ?string $excludedDatabaseTables;

    public ?Collection $availableTags;
    public ?array $selectedTags;

    public function updatedUseCustomCron(): void
    {
        $this->useCustomCron = (bool) $this->useCustomCron;
    }

    public function updatedBackupType(): void
    {
        $this->backupType = (string) $this->backupType;

        if ($this->backupType === BackupTask::TYPE_FILES) {
            $this->remoteServers = Auth::user()->remoteServers;
        }

        if ($this->backupType === BackupTask::TYPE_DATABASE) {
            $this->remoteServers = Auth::user()->remoteServers->where('database_password', '!=', null);
        }

        // Reset the remote server ID to ensure it matches the new type
        $this->remoteServerId = $this->remoteServers->first()?->id ?? '';
    }

    public function mount(): void
    {
        $this->availableTags = Auth::user()->tags;
        $this->selectedTags = $this->backupTask->tags->pluck('id')->toArray();

        $this->backupTimes = collect(range(0, 47))->map(function ($halfHour) {
            $hour = intdiv($halfHour, 2);
            $minute = ($halfHour % 2) * 30;

            return sprintf('%02d:%02d', $hour, $minute);
        });

        $this->remoteServers = Auth::user()->remoteServers->where('database_password', null);
        $this->remoteServerId = $this->remoteServers->first()?->id ?? '';
        $this->backupDestinationId = Auth::user()->backupDestinations->first()?->id ?? '';

        $this->updatedBackupType(); // Ensure the initial remoteServers collection is set correctly

        $this->label = $this->backupTask->label;
        $this->description = $this->backupTask->description;
        $this->sourcePath = $this->backupTask->source_path ?? null;
        $this->remoteServerId = $this->backupTask->remote_server_id;
        $this->backupDestinationId = $this->backupTask->backup_destination_id;
        $this->frequency = $this->backupTask->frequency ?? null;
        $this->cronExpression = $this->backupTask->custom_cron_expression;
        $this->backupsToKeep = $this->backupTask->maximum_backups_to_keep;
        $this->backupType = $this->backupTask->type;
        $this->databaseName = $this->backupTask->database_name;
        $this->appendedFileName = $this->backupTask->appended_file_name;
        $this->notifyEmail = $this->backupTask->notify_email ?? null;
        $this->notifyDiscordWebhook = $this->backupTask->notify_discord_webhook ?? null;
        $this->notifySlackWebhook = $this->backupTask->notify_slack_webhook ?? null;
        $this->userTimezone = Auth::user()->timezone ?? 'UTC';
        $this->storePath = $this->backupTask->store_path;
        $this->excludedDatabaseTables = $this->backupTask->excluded_database_tables ?? null;

        if ($this->backupTask->time_to_run_at) {
            $this->timeToRun = Carbon::createFromFormat('H:i', $this->backupTask->time_to_run_at, 'UTC')?->setTimezone($this->userTimezone)->format('H:i');
        }

        if ($this->cronExpression) {
            $this->useCustomCron = true;
        }
    }

    public function submit(): RedirectResponse|Redirector
    {
        $this->authorize('update', $this->backupTask);

        $messages = [
            'selectedTags.*.exists' => __('One or more of the selected tags do not exist.'),
            'excludedDatabaseTables.regex' => __('Please enter a valid list of table names separated by commas.'),
            'storePath.regex' => __('The path must be a valid Unix path.'),
            'notifyEmail.email' => __('Please enter a valid email address.'),
            'notifySlackWebhook.url' => __('Please enter a valid URL.'),
            'notifySlackWebhook.starts_with' => __('Please enter a valid Slack webhook URL.'),
            'notifyDiscordWebhook.url' => __('Please enter a valid URL.'),
            'notifyDiscordWebhook.starts_with' => __('Please enter a valid Discord webhook URL.'),
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
            'sourcePath.required_unless' => __('Please enter the source path for the backup task.'),
            'remoteServerId.required' => __('Please choose a remote server.'),
            'backupDestinationId.required' => __('Please choose a backup destination.'),
            'frequency.required' => __('Please choose a frequency for the backup task.'),
            'timeToRun.required_unless' => __('Please select a time to run the backup task.'),
            'cronExpression.required_if' => __('Please enter a cron expression for the backup task.'),
            'sourcePath.regex' => __('The path must be a valid Unix path.'),
            'timeToRun.regex' => __('You have entered an invalid time. Please enter a time in the format HH:MM.'),
            'cronExpression.regex' => __('You have entered an invalid cron expression.'),
        ];

        if ($this->backupType === 'files') {
            $this->validate([
                'selectedTags' => ['nullable', 'array', Rule::exists('tags', 'id')->where('user_id', Auth::id())],
                'excludedDatabaseTables' => ['nullable', 'string', 'regex:/^([a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)*)$/'],
                'storePath' => ['nullable', 'string', 'regex:/^(\/[^\/\0]+)+\/?$/'], // Unix path regex
                'notifyEmail' => ['nullable', 'email'],
                'notifySlackWebhook' => ['nullable', 'url', 'starts_with:https://hooks.slack.com/services/'],
                'notifyDiscordWebhook' => ['nullable', 'url', 'starts_with:https://discord.com/api/webhooks/'],
                'appendedFileName' => ['nullable', 'string', 'max:40', 'alpha_dash'],
                'backupType' => ['required', 'string', 'in:files,database'],
                'backupsToKeep' => ['required', 'integer', 'min:0', 'max:50'],
                'label' => ['required', 'string'],
                'description' => ['nullable', 'string', 'max:100'],
                'databaseName' => ['nullable', 'string', 'required_if:backupType,database'],
                'remoteServerId' => ['required', 'string', 'exists:remote_servers,id'],
                'backupDestinationId' => ['required', 'string', 'exists:backup_destinations,id'],
                'frequency' => ['required', 'string', 'in:daily,weekly'],
                'timeToRun' => ['string', 'regex:/^([01]?\d|2[0-3]):([0-5]?\d)$/', 'required_unless:useCustomCron,true', new UniqueScheduledTimePerRemoteServer((int) $this->remoteServerId, $this->backupTask->id)],
                'cronExpression' => ['nullable', 'string', 'regex:/^(\*|([0-5]?\d)) (\*|([01]?\d|2[0-3])) (\*|([0-2]?\d|3[01])) (\*|([1-9]|1[0-2])) (\*|([0-7]))$/', 'required_if:useCustomCron,true'],
                'sourcePath' => ['required', 'string', 'regex:/^(\/[^\/\0]+)+\/?$/'],
            ], $messages);
        }

        $this->validate([
            'selectedTags' => ['nullable', 'array', Rule::exists('tags', 'id')->where('user_id', Auth::id())],
            'excludedDatabaseTables' => ['nullable', 'string', 'regex:/^([a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)*)$/'],
            'storePath' => ['nullable', 'string', 'regex:/^(\/[^\/\0]+)+\/?$/'], // Unix path regex
            'notifyEmail' => ['nullable', 'email'],
            'notifySlackWebhook' => ['nullable', 'url', 'starts_with:https://hooks.slack.com/services/'],
            'notifyDiscordWebhook' => ['nullable', 'url', 'starts_with:https://discord.com/api/webhooks/'],
            'appendedFileName' => ['nullable', 'string', 'max:40', 'alpha_dash'],
            'backupType' => ['required', 'string', 'in:files,database'],
            'backupsToKeep' => ['required', 'integer', 'min:0', 'max:50'],
            'label' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:100'],
            'databaseName' => ['nullable', 'string', 'required_if:backupType,database'],
            'remoteServerId' => ['required', 'string', 'exists:remote_servers,id'],
            'backupDestinationId' => ['required', 'string', 'exists:backup_destinations,id'],
            'frequency' => ['required', 'string', 'in:daily,weekly'],
            'timeToRun' => ['string', 'regex:/^([01]?\d|2[0-3]):([0-5]?\d)$/', 'required_unless:useCustomCron,true', new UniqueScheduledTimePerRemoteServer((int) $this->remoteServerId, $this->backupTask->id)],
            'cronExpression' => ['nullable', 'string', 'regex:/^(\*|([0-5]?\d)) (\*|([01]?\d|2[0-3])) (\*|([0-2]?\d|3[01])) (\*|([1-9]|1[0-2])) (\*|([0-7]))$/', 'required_if:useCustomCron,true'],
        ], $messages);

        if ($this->cronExpression) {
            $this->timeToRun = null;
            $this->frequency = null;
        }

        if ($this->timeToRun && $this->frequency) {
            $this->cronExpression = null;
        }

        if ($this->userTimezone !== 'UTC' && $this->timeToRun) {
            $this->timeToRun = Carbon::createFromFormat('H:i', $this->timeToRun, $this->userTimezone)?->setTimezone('UTC')->format('H:i');
        }

        $this->backupTask->update([
            'excluded_database_tables' => $this->excludedDatabaseTables ?? null,
            'user_id' => Auth::id(),
            'remote_server_id' => $this->remoteServerId,
            'backup_destination_id' => $this->backupDestinationId,
            'label' => $this->label,
            'description' => $this->description ?: '',
            'source_path' => $this->sourcePath ?? null,
            'frequency' => $this->frequency,
            'time_to_run_at' => $this->timeToRun,
            'custom_cron_expression' => $this->cronExpression,
            'maximum_backups_to_keep' => $this->backupsToKeep,
            'type' => $this->backupType,
            'database_name' => $this->databaseName,
            'appended_file_name' => $this->appendedFileName,
            'notify_email' => $this->notifyEmail ?? null,
            'notify_discord_webhook' => $this->notifyDiscordWebhook ?? null,
            'notify_slack_webhook' => $this->notifySlackWebhook ?? null,
            'store_path' => $this->storePath ?? null,
        ]);

        $this->backupTask->tags()->sync($this->selectedTags);

        Toaster::success(__('Backup task details saved.'));

        return Redirect::route('backup-tasks.index');
    }

    public function render(): View
    {
        $backupTypes = [
            BackupTask::TYPE_FILES => __('files'),
            BackupTask::TYPE_DATABASE => __('database')];

        return view('livewire.backup-tasks.update-backup-task-form', [
            'backupTimes' => $this->backupTimes,
            'backupDestinations' => Auth::user()->backupDestinations,
            'backupTypes' => $backupTypes,
            'remoteServers' => $this->remoteServers,
        ]);
    }
}
