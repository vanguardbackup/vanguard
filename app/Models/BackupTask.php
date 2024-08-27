<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\BackupTasks\SendDiscordNotificationJob;
use App\Jobs\BackupTasks\SendPushoverNotificationJob;
use App\Jobs\BackupTasks\SendSlackNotificationJob;
use App\Jobs\BackupTasks\SendTeamsNotificationJob;
use App\Jobs\BackupTasks\SendTelegramNotificationJob;
use App\Jobs\RunDatabaseBackupTaskJob;
use App\Jobs\RunFileBackupTaskJob;
use App\Mail\BackupTasks\OutputMail;
use App\Models\Traits\ComposesTelegramNotification;
use App\Traits\HasTags;
use Carbon\CarbonInterface;
use Cron\CronExpression;
use Database\Factories\BackupTaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Motomedialab\SimpleLaravelAudit\Traits\AuditableModel;
use Number;
use RuntimeException;

/**
 * Represents a backup task in the system.
 *
 * This model handles the creation, scheduling, execution, and monitoring of backup tasks.
 * It includes methods for managing task status, notifications, and interactions with remote servers and backup destinations.
 */
class BackupTask extends Model
{
    use AuditableModel;
    use ComposesTelegramNotification;
    /** @use HasFactory<BackupTaskFactory> */
    use HasFactory;
    use HasTags;

    public const string STATUS_READY = 'ready';
    public const string STATUS_RUNNING = 'running';
    public const string FREQUENCY_DAILY = 'daily';
    public const string FREQUENCY_WEEKLY = 'weekly';
    public const string TYPE_FILES = 'files';
    public const string TYPE_DATABASE = 'database';

    protected $guarded = [];

    /**
     * Get the count of tasks per month for the last six months for a given user.
     *
     * @param  int  $userId  The ID of the user
     * @return array<string, int> An array of task counts indexed by month
     */
    public static function logsCountPerMonthForLastSixMonths(int $userId): array
    {
        $user = User::whereId($userId)->firstOrFail();

        $endDate = now();
        $startDate = $endDate->copy()->subMonths(6)->startOfMonth();

        $locale = $user->language ?? 'en';
        Carbon::setLocale($locale);

        $results = BackupTaskData::query()
            ->join('backup_tasks', 'backup_tasks.id', '=', 'backup_task_data.backup_task_id')
            ->where('backup_tasks.user_id', $userId)
            ->where('backup_task_data.created_at', '>=', $startDate)
            ->where('backup_task_data.created_at', '<=', $endDate)
            ->selectRaw("COUNT(*) as count, DATE_TRUNC('month', backup_task_data.created_at) as month_date")
            ->groupBy('month_date')
            ->orderBy('month_date')
            ->get();

        return $results->mapWithKeys(function ($item) use ($locale): array {
            $carbonDate = Carbon::parse($item->getAttribute('month_date'))->locale($locale);
            $localizedMonth = ucfirst((string) $carbonDate->isoFormat('MMM YYYY'));

            return [$localizedMonth => $item->getAttribute('count')];
        })->toArray();
    }

    /**
     * Get the count of backup tasks by type for a given user.
     *
     * @param  int  $userId  The ID of the user
     * @return array<string, int> An array of task counts indexed by type
     */
    public static function backupTasksCountByType(int $userId): array
    {
        /** @var Collection<int, Model> $results */
        $results = self::query()
            ->where('user_id', $userId)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        return $results->mapWithKeys(function ($item): array {
            $type = $item->getAttributeValue('type');

            /** @var string $localizedType */
            $localizedType = is_string($type) ? __($type) : __('Unknown');

            $count = (int) $item->getAttribute('count');

            return [$localizedType => $count];
        })
            ->toArray();
    }

    /**
     * Get backup tasks data for the past 90 days
     *
     * @return array<string, mixed> An array containing backup task data
     */
    public static function getBackupTasksData(): array
    {
        $startDate = now()->subDays(89);
        $endDate = now();

        $backupTasks = self::selectRaw('DATE(created_at) as date, type, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        $dates = Collection::make($startDate->daysUntil($endDate)->toArray())
            ->map(fn (CarbonInterface $carbon): string => $carbon->format('Y-m-d'));

        $fileBackups = $databaseBackups = array_fill_keys($dates->toArray(), 0);

        foreach ($backupTasks as $backupTask) {
            $date = $backupTask['date'];
            $count = (int) $backupTask['count'];
            if ($backupTask['type'] === 'Files') {
                $fileBackups[$date] = $count;
            } else {
                $databaseBackups[$date] = $count;
            }
        }

        return [
            'backupDates' => $dates->values()->toArray(),
            'fileBackupCounts' => array_values($fileBackups),
            'databaseBackupCounts' => array_values($databaseBackups),
        ];
    }

    /**
     * Get backup success rate data for the past 6 months
     *
     * @return array<string, array<int, string|float>> An array containing success rate data
     */
    public static function getBackupSuccessRateData(): array
    {
        $startDate = now()->startOfMonth()->subMonths(5);
        $endDate = now()->endOfMonth();

        $backupLogs = BackupTaskLog::selectRaw("DATE_TRUNC('month', created_at)::date as month")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN successful_at IS NOT NULL THEN 1 ELSE 0 END) as successful')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels = $backupLogs->pluck('month')->map(fn ($date): string => Carbon::parse($date)->format('Y-m'))->toArray();
        $data = $backupLogs->map(function ($log): float {
            $total = (int) ($log['total'] ?? 0);
            $successful = (int) ($log['successful'] ?? 0);

            return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
        })->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get average backup size data
     *
     * @return array<string, array<int, string>> An array containing average backup size data
     */
    public static function getAverageBackupSizeData(): array
    {
        $backupSizes = self::join('backup_task_data', 'backup_tasks.id', '=', 'backup_task_data.backup_task_id')
            ->join('backup_task_logs', 'backup_tasks.id', '=', 'backup_task_logs.backup_task_id')
            ->select('backup_tasks.type')
            ->selectRaw('AVG(backup_task_data.size) as average_size')
            ->whereNotNull('backup_task_logs.successful_at')
            ->groupBy('backup_tasks.type')
            ->get();

        return [
            'labels' => $backupSizes->pluck('type')->toArray(),
            'data' => $backupSizes->pluck('average_size')
                ->map(fn ($size): string => self::formatFileSize((int) $size))
                ->toArray(),
        ];
    }

    /**
     * Get completion time data for the past 3 months
     *
     * @return array<string, array<int, string|float>> An array containing completion time data
     */
    public static function getCompletionTimeData(): array
    {
        $startDate = now()->subMonths(3);
        $endDate = now();

        $completionTimes = BackupTaskData::join('backup_task_logs', 'backup_task_data.backup_task_id', '=', 'backup_task_logs.backup_task_id')
            ->selectRaw('DATE(backup_task_logs.created_at) as date')
            ->selectRaw("
            AVG(
                CASE
                    WHEN backup_task_data.duration ~ '^\\d+$' THEN backup_task_data.duration::integer
                    WHEN backup_task_data.duration ~ '^(\\d+):(\\d+):(\\d+)$' THEN
                        (SUBSTRING(backup_task_data.duration FROM '^(\\d+)'))::integer * 3600 +
                        (SUBSTRING(backup_task_data.duration FROM '^\\d+:(\\d+)'))::integer * 60 +
                        (SUBSTRING(backup_task_data.duration FROM ':(\\d+)$'))::integer
                    ELSE 0
                END
            ) as avg_duration
        ")
            ->whereBetween('backup_task_logs.created_at', [$startDate, $endDate])
            ->whereNotNull('backup_task_logs.successful_at')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $completionTimes->pluck('date')->toArray(),
            'data' => $completionTimes->pluck('avg_duration')
                ->map(fn ($duration): float => round($duration / 60, 2))
                ->toArray(),
        ];
    }

    /**
     * Format file size using the Number facade
     *
     * @param  int  $bytes  The size in bytes
     * @return string The formatted file size
     */
    private static function formatFileSize(int $bytes): string
    {
        return Number::fileSize($bytes);
    }

    /**
     * Scope query to include only non-paused backup tasks.
     *
     * @param  Builder<BackupTask>  $builder
     * @return Builder<BackupTask>
     */
    public function scopeNotPaused(Builder $builder): Builder
    {
        return $builder->whereNull('paused_at');
    }

    /**
     * Scope query to include only ready backup tasks.
     *
     * @param  Builder<BackupTask>  $builder
     * @return Builder<BackupTask>
     */
    public function scopeReady(Builder $builder): Builder
    {
        return $builder->where('status', self::STATUS_READY);
    }

    /**
     * Get the user that owns the backup task.
     *
     * @return BelongsTo<User, BackupTask>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the backup destination for this task.
     *
     * @return BelongsTo<BackupDestination, BackupTask>
     */
    public function backupDestination(): BelongsTo
    {
        return $this->belongsTo(BackupDestination::class);
    }

    /**
     * Get the remote server for this task.
     *
     * @return BelongsTo<RemoteServer, BackupTask>
     */
    public function remoteServer(): BelongsTo
    {
        return $this->belongsTo(RemoteServer::class);
    }

    /**
     * Get the logs for this backup task.
     *
     * @return HasMany<BackupTaskLog>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(BackupTaskLog::class);
    }

    /**
     * Get the data associated with this backup task.
     *
     * @return HasMany<BackupTaskData>
     */
    public function data(): HasMany
    {
        return $this->hasMany(BackupTaskData::class);
    }

    /**
     * Update the last run timestamp for this task.
     */
    public function updateLastRanAt(): void
    {
        $this->update(['last_run_at' => now()]);
        $this->save();
    }

    /**
     * Check if the task is using a custom cron expression.
     */
    public function usingCustomCronExpression(): bool
    {
        return ! is_null($this->custom_cron_expression);
    }

    /**
     * Check if the task is currently running.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if the task is ready to run.
     */
    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    /**
     * Mark the task as running.
     */
    public function markAsRunning(): void
    {
        $this->update(['status' => 'running']);
        $this->save();
    }

    /**
     * Mark the task as ready.
     */
    public function markAsReady(): void
    {
        $this->update(['status' => 'ready']);
        $this->save();
    }

    /**
     * Check if it's the right time to run the task based on its schedule.
     */
    public function isTheRightTimeToRun(): bool
    {
        if ($this->isDaily()) {
            return $this->time_to_run_at === now()->format('H:i');
        }

        if ($this->isWeekly()) {
            if ($this->time_to_run_at === now()->format('H:i') && $this->getAttribute('last_scheduled_weekly_run_at') === null) {
                return true;
            }

            if ($this->time_to_run_at === now()->format('H:i') && $this->getAttribute('last_scheduled_weekly_run_at')?->isLastWeek()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the task is eligible to run now.
     */
    public function eligibleToRunNow(): bool
    {
        if ($this->isRunning()) {
            return false;
        }

        if ($this->isPaused()) {
            return false;
        }

        if ($this->isReady() && $this->usingCustomCronExpression()) {
            return $this->cronExpressionMatches();
        }

        if (! $this->isReady()) {
            return false;
        }

        if ($this->usingCustomCronExpression()) {
            return false;
        }

        return $this->isTheRightTimeToRun();
    }

    /**
     * Check if the current time matches the task's cron expression.
     */
    public function cronExpressionMatches(): bool
    {
        return $this->cronExpression()->isDue();
    }

    /**
     * Check if the task is scheduled to run daily.
     */
    public function isDaily(): bool
    {
        return $this->frequency === self::FREQUENCY_DAILY;
    }

    /**
     * Check if the task is scheduled to run weekly.
     */
    public function isWeekly(): bool
    {
        return $this->frequency === self::FREQUENCY_WEEKLY;
    }

    /**
     * Update the last scheduled weekly run timestamp.
     */
    public function updateScheduledWeeklyRun(): void
    {
        if (! $this->isWeekly()) {
            return;
        }

        $this->update(['last_scheduled_weekly_run_at' => now()]);
        $this->save();
    }

    /**
     * Check if the task is set to rotate backups.
     */
    public function isRotatingBackups(): bool
    {
        return $this->maximum_backups_to_keep > 0;
    }

    /**
     * Run the backup task.
     */
    public function run(): void
    {
        if ($this->user?->hasDisabledAccount()) {
            Log::warning('Cannot run backup tasks for this user as they have a disabled account.');

            return;
        }

        if ($this->isPaused()) {
            Log::debug(sprintf('Task %s is paused, skipping run', $this->id));

            return;
        }

        if ($this->isAnotherTaskRunningOnSameRemoteServer()) {
            Log::debug('Another task is running on the same remote server, skipping run for task ' . $this->id . ' for now.');

            return;
        }

        if ($this->isFilesType()) {
            RunFileBackupTaskJob::dispatch($this->id)
                ->onQueue('backup-tasks');
        }

        if ($this->isDatabaseType()) {
            RunDatabaseBackupTaskJob::dispatch($this->id)
                ->onQueue('backup-tasks');
        }
    }

    /**
     * Check if the task is for file backup.
     */
    public function isFilesType(): bool
    {
        return $this->type === self::TYPE_FILES;
    }

    /**
     * Check if the task is for database backup.
     */
    public function isDatabaseType(): bool
    {
        return $this->type === self::TYPE_DATABASE;
    }

    /**
     * Calculate the next run time for the task.
     */
    public function calculateNextRun(): ?Carbon
    {
        if (is_null($this->frequency) && $this->custom_cron_expression) {
            $cronExpression = new CronExpression($this->custom_cron_expression);

            return Carbon::instance($cronExpression->getNextRunDate(Carbon::now()));
        }

        if ($this->frequency === self::FREQUENCY_DAILY) {
            $nextRun = Carbon::today()->setTimeFromTimeString((string) $this->time_to_run_at);
            if ($nextRun->lte(Carbon::now())) {
                $nextRun->addDay();
            }

            return $nextRun;
        }

        if ($this->frequency === self::FREQUENCY_WEEKLY) {
            if ($this->last_scheduled_weekly_run_at) {
                return Carbon::parse($this->last_scheduled_weekly_run_at)->addWeek();
            }

            return Carbon::today()->addDays(7 - Carbon::today()->dayOfWeek + Carbon::parse($this->time_to_run_at)->dayOfWeek)
                ->setTimeFromTimeString((string) $this->time_to_run_at);
        }

        return null;
    }

    /**
     * Pause the backup task.
     */
    public function pause(): void
    {
        $this->update(['paused_at' => now()]);
        $this->save();
    }

    /**
     * Resume the backup task.
     */
    public function resume(): void
    {
        $this->update(['paused_at' => null]);
        $this->save();
    }

    /**
     * Check if the task is paused.
     */
    public function isPaused(): bool
    {
        return ! is_null($this->paused_at);
    }

    /**
     * Check if the task has a file name appended.
     */
    public function hasFileNameAppended(): bool
    {
        return ! is_null($this->appended_file_name);
    }

    /**
     * Set the script update time.
     */
    public function setScriptUpdateTime(): void
    {
        $this->update(['last_script_update_at' => now()]);
        $this->saveQuietly();
    }

    /**
     * Reset the script update time.
     */
    public function resetScriptUpdateTime(): void
    {
        $this->update(['last_script_update_at' => null]);
        $this->save();
    }

    /**
     * Check if the task has email notifications enabled.
     */
    public function hasEmailNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_EMAIL)
            ->exists();
    }

    /**
     * Check if the task has Discord notifications enabled.
     */
    public function hasDiscordNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_DISCORD)
            ->exists();
    }

    /**
     * Check if the task has Slack notifications enabled.
     */
    public function hasSlackNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_SLACK)
            ->exists();
    }

    /**
     * Check if the task has Microsoft Teams notifications enabled.
     */
    public function hasTeamNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_TEAMS)
            ->exists();
    }

    /**
     * Check if the task has Pushover notifications enabled.
     */
    public function hasPushoverNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_PUSHOVER)
            ->exists();
    }

    /**
     * Check if the task has Telegram notifications enabled.
     */
    public function hasTelegramNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_TELEGRAM)
            ->exists();
    }

    /**
     * Send notifications for the latest backup task log.
     *
     * This method handles sending notifications through various streams (Email, Discord, Slack etc)
     * based on the backup task outcome and user preferences.
     */
    public function sendNotifications(): void
    {
        $jobQueue = 'backup-task-notifications';

        /** @var BackupTaskLog|null $latestLog */
        $latestLog = $this->fresh()?->logs()->latest()->first();

        if (! $latestLog) {
            return;
        }

        $backupTaskSuccessful = $latestLog->getAttribute('successful_at') !== null;

        /** @var Collection<int, NotificationStream> $notificationStreams */
        $notificationStreams = $this->notificationStreams;

        foreach ($notificationStreams as $notificationStream) {
            if (! $this->shouldSendNotification($notificationStream, $backupTaskSuccessful)) {
                continue;
            }

            $this->dispatchNotification($notificationStream, $latestLog, $jobQueue);
        }
    }

    /**
     * Determine if a notification should be sent based on the stream settings and backup outcome.
     */
    public function shouldSendNotification(NotificationStream $notificationStream, bool $backupTaskSuccessful): bool
    {
        if ($notificationStream->getAttribute('user') && $notificationStream->getAttribute('user')->hasQuietMode()) {
            return false;
        }

        if ($backupTaskSuccessful && $notificationStream->hasSuccessfulBackupNotificationsEnabled()) {
            return true;
        }

        return ! $backupTaskSuccessful && $notificationStream->hasFailedBackupNotificationsEnabled();
    }

    /**
     * Dispatch the appropriate notification based on the stream type.
     *
     * @throws InvalidArgumentException If an unsupported notification type is encountered.
     */
    public function dispatchNotification(NotificationStream $notificationStream, BackupTaskLog $backupTaskLog, string $queue): void
    {
        $streamValue = (string) $notificationStream->getAttribute('value');

        /* This is one of the additional fields, out of two, to supply additional data for the external service to operate. */
        $additionalStreamValueOne = (string) $notificationStream->getAttribute('additional_field_one');

        match ($notificationStream->getAttribute('type')) {
            NotificationStream::TYPE_EMAIL => $this->sendEmailNotification($backupTaskLog, $streamValue),
            NotificationStream::TYPE_DISCORD => SendDiscordNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            NotificationStream::TYPE_SLACK => SendSlackNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            NotificationStream::TYPE_TEAMS => SendTeamsNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            NotificationStream::TYPE_PUSHOVER => SendPushoverNotificationJob::dispatch($this, $backupTaskLog, $streamValue, $additionalStreamValueOne)->onQueue($queue),
            NotificationStream::TYPE_TELEGRAM => SendTelegramNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            default => throw new InvalidArgumentException("Unsupported notification type: {$notificationStream->getAttribute('type')}"),
        };
    }

    /**
     * Send an email notification for the backup task.
     */
    public function sendEmailNotification(BackupTaskLog $backupTaskLog, string $emailAddress): void
    {
        Mail::to($emailAddress)
            ->queue(new OutputMail($backupTaskLog));
    }

    /**
     * Send a Discord webhook notification for the backup task.
     *
     * @throws RuntimeException|ConnectionException If the Discord webhook request fails.
     */
    public function sendDiscordWebhookNotification(BackupTaskLog $backupTaskLog, string $webhookURL): void
    {
        $status = $backupTaskLog->getAttribute('successful_at') ? 'success' : 'failure';
        $message = $backupTaskLog->getAttribute('successful_at')
            ? 'The backup task was successful. Please see the details below for more information about this task.'
            : 'The backup task failed. Please see the details below for more information about this task.';
        $color = $backupTaskLog->getAttribute('successful_at') ? 3066993 : 15158332; // Green for success, Red for failure

        $embed = [
            'title' => $this->label . ' Backup Task',
            'description' => $message,
            'color' => $color,
            'fields' => [
                [
                    'name' => __('Backup Type'),
                    'value' => ucfirst($this->type),
                    'inline' => true,
                ],
                [
                    'name' => __('Remote Server'),
                    'value' => $this->remoteServer?->getAttribute('label'),
                    'inline' => true,
                ],
                [
                    'name' => __('Backup Destination'),
                    'value' => $this->backupDestination?->getAttribute('label') . ' (' . $this->backupDestination?->type() . ')',
                    'inline' => true,
                ],
                [
                    'name' => __('Result'),
                    'value' => ucfirst($status),
                    'inline' => true,
                ],
                [
                    'name' => __('Ran at'),
                    'value' => Carbon::parse($backupTaskLog->getAttribute('created_at'))->format('jS F Y, H:i:s'),
                    'inline' => true,
                ],
            ],
            'footer' => [
                'icon_url' => asset('notification-streams-assets/images/icon.png'),
                'text' => __('This notification was sent by :app.', ['app' => config('app.name')]),
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($webhookURL, [
            'username' => config('app.name'),
            'avatar_url' => asset('notification-streams-assets/images/icon.png'),
            'embeds' => [$embed],
        ]);

        if ($response->status() !== 204) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['message'] ?? 'Unknown error';
            throw new RuntimeException("Discord webhook failed: {$errorMessage}");
        }
    }

    /**
     * Send a Slack webhook notification for the backup task.
     *
     * @throws RuntimeException|ConnectionException If the Slack webhook request fails.
     */
    public function sendSlackWebhookNotification(BackupTaskLog $backupTaskLog, string $webhookURL): void
    {
        $status = $backupTaskLog->getAttribute('successful_at') ? 'success' : 'failure';
        $message = $backupTaskLog->getAttribute('successful_at')
            ? 'The backup task was successful. Please see the details below for more information about this task.'
            : 'The backup task failed. Please see the details below for more information about this task.';
        $color = $backupTaskLog->getAttribute('successful_at') ? 'good' : 'danger'; // Green for success, Red for failure

        $payload = [
            'attachments' => [
                [
                    'title' => $this->label . ' Backup Task',
                    'text' => $message,
                    'color' => $color,
                    'fields' => [
                        [
                            'title' => __('Backup Type'),
                            'value' => ucfirst($this->type),
                            'short' => true,
                        ],
                        [
                            'title' => __('Remote Server'),
                            'value' => $this->remoteServer?->label,
                            'short' => true,
                        ],
                        [
                            'title' => __('Backup Destination'),
                            'value' => $this->backupDestination?->label . ' (' . $this->backupDestination?->type() . ')',
                            'short' => true,
                        ],
                        [
                            'title' => __('Result'),
                            'value' => ucfirst($status),
                            'short' => true,
                        ],
                        [
                            'title' => __('Ran at'),
                            'value' => Carbon::parse($backupTaskLog->getAttribute('created_at'))->format('jS F Y, H:i:s'),
                            'short' => true,
                        ],
                    ],
                    'footer' => __('This notification was sent by :app.', ['app' => config('app.name')]),
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($webhookURL, $payload);

        if ($response->status() !== 200 || $response->body() !== 'ok') {
            throw new RuntimeException('Slack webhook failed: ' . $response->body());
        }
    }

    /**
     * Send a Microsoft Teams webhook notification for the backup task.
     *
     * @throws RuntimeException|ConnectionException If the Teams webhook request fails.
     */
    public function sendTeamsWebhookNotification(BackupTaskLog $backupTaskLog, string $webhookURL): void
    {
        $status = $backupTaskLog->getAttribute('successful_at') ? 'success' : 'failure';
        $message = $backupTaskLog->getAttribute('successful_at')
            ? 'The backup task was successful. Please see the details below for more information about this task.'
            : 'The backup task failed. Please see the details below for more information about this task.';
        $color = $backupTaskLog->getAttribute('successful_at') ? '00FF00' : 'FF0000'; // Green for success, Red for failure

        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => $color,
            'summary' => $this->label . ' Backup Task',
            'sections' => [
                [
                    'activityTitle' => $this->label . ' Backup Task',
                    'activitySubtitle' => $message,
                    'facts' => [
                        [
                            'name' => __('Backup Type'),
                            'value' => ucfirst($this->type),
                        ],
                        [
                            'name' => __('Remote Server'),
                            'value' => $this->remoteServer?->label,
                        ],
                        [
                            'name' => __('Backup Destination'),
                            'value' => $this->backupDestination?->label . ' (' . $this->backupDestination?->type() . ')',
                        ],
                        [
                            'name' => __('Result'),
                            'value' => ucfirst($status),
                        ],
                        [
                            'name' => __('Ran at'),
                            'value' => Carbon::parse($backupTaskLog->getAttribute('created_at'))->format('jS F Y, H:i:s'),
                        ],
                    ],
                    'text' => __('This notification was sent by :app.', ['app' => config('app.name')]),
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($webhookURL, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Teams webhook failed: ' . $response->body());
        }
    }

    /**
     * Send a Pushover notification for a backup task.
     *
     * @param  BackupTaskLog  $backupTaskLog  The log entry for the backup task
     * @param  string  $pushoverToken  The Pushover API token
     */
    public function sendPushoverNotification(BackupTaskLog $backupTaskLog, string $pushoverToken, string $userToken): void
    {
        $isSuccessful = $backupTaskLog->getAttribute('successful_at') !== null;
        $status = $isSuccessful ? 'success' : 'failure';
        $message = $isSuccessful
            ? 'The backup task was successful.'
            : 'The backup task failed.';
        $priority = $isSuccessful ? 0 : 1; // Normal priority for success, high priority for failure

        $payload = [
            'token' => $pushoverToken,
            'user' => $userToken,
            'title' => "{$this->label} Backup Task: " . ucfirst($status),
            'message' => $message . " Details:\n" .
                'Backup Type: ' . ucfirst($this->type) . "\n" .
                'Remote Server: ' . ($this->remoteServer?->label ?? 'N/A') . "\n" .
                'Backup Destination: ' . ($this->backupDestination?->label ?? 'N/A') .
                ' (' . ($this->backupDestination?->type() ?? 'N/A') . ")\n" .
                'Ran at: ' . Carbon::parse($backupTaskLog->getAttribute('created_at'))->format('jS F Y, H:i:s'),
            'priority' => $priority,
        ];

        $response = Http::post('https://api.pushover.net/1/messages.json', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Pushover notification failed: ' . $response->body());
        }
    }

    /**
     * Send a Telegram notification for the backup task.
     *
     * @param  BackupTaskLog  $backupTaskLog  The log entry for the backup task
     * @param  string  $chatID  The target Telegram chat ID
     *
     * @throws RuntimeException|ConnectionException If the Telegram request fails.
     */
    public function sendTelegramNotification(BackupTaskLog $backupTaskLog, string $chatID): void
    {
        $url = $this->getTelegramUrl();
        $message = $this->composeTelegramNotificationText($this, $backupTaskLog);
        $payload = [
            'chat_id' => $chatID,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        $response = Http::post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Telegram notification failed: ' . $response->body());
        }
    }

    /**
     * Check if the task has a custom store path.
     */
    public function hasCustomStorePath(): bool
    {
        return ! is_null($this->store_path);
    }

    /**
     * Check if another task is running on the same remote server.
     */
    public function isAnotherTaskRunningOnSameRemoteServer(): bool
    {
        return static::query()
            ->where('remote_server_id', $this->remote_server_id)
            ->where('status', static::STATUS_RUNNING)
            ->where('id', '<>', $this->id)
            ->exists();
    }

    /**
     * Get a comma-separated list of attached tag labels.
     */
    public function listOfAttachedTagLabels(): ?string
    {
        if ($this->tags->isEmpty()) {
            return null;
        }

        return $this->tags->pluck('label')->implode(', ');
    }

    /**
     * Check if the task has isolated credentials.
     */
    public function hasIsolatedCredentials(): bool
    {
        return $this->getAttribute('isolated_username') !== null && $this->getAttribute('isolated_password') !== null;
    }

    /**
     * Check if the task has an encryption password set.
     */
    public function hasEncryptionPassword(): bool
    {
        return $this->getAttribute('encryption_password') !== null;
    }

    /**
     * Format the backup task's last run time according to the user's locale preferences.
     */
    public function lastRunFormatted(?User $user = null): ?string
    {
        if (is_null($this->last_run_at)) {
            return null;
        }

        $user ??= Auth::user();

        $locale = $user?->language ?? config('app.locale');

        Carbon::setLocale($locale);

        return Carbon::parse($this->last_run_at)
            ->timezone($user->timezone ?? config('app.timezone'))
            ->locale($locale)
            ->isoFormat('D MMMM YYYY HH:mm');
    }

    /**
     * Format the backup task's run time according to the user's timezone.
     */
    public function runTimeFormatted(?User $user = null): ?string
    {
        if ($this->time_to_run_at === null) {
            return null;
        }

        $userTimezone = $user?->timezone ?? Auth::user()?->timezone ?? config('app.timezone');

        $utcTime = Carbon::parse($this->time_to_run_at, 'UTC');

        return $utcTime->timezone($userTimezone)->format('H:i');
    }

    /**
     * Get the latest log for the backup task.
     *
     * @return HasOne<BackupTaskLog>
     */
    public function latestLog(): HasOne
    {
        return $this->hasOne(BackupTaskLog::class)->latest();
    }

    /**
     * Get the notifications streams linked to this backup task.
     *
     * @return BelongsToMany<NotificationStream>
     */
    public function notificationStreams(): BelongsToMany
    {
        return $this->belongsToMany(NotificationStream::class, 'backup_task_notification_streams')
            ->using(BackupTaskNotificationStream::class)
            ->withTimestamps();
    }

    /**
     * Get the casts array for the model's attributes.
     *
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'last_run_at' => 'datetime',
            'last_scheduled_weekly_run_at' => 'datetime',
            'encryption_password' => 'encrypted',
        ];
    }

    /**
     * Returns the cron expression for the task.
     */
    private function cronExpression(): CronExpression
    {
        return new CronExpression((string) $this->custom_cron_expression);
    }
}
