<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\BackupTasks\SendDiscordNotificationJob;
use App\Jobs\BackupTasks\SendSlackNotificationJob;
use App\Jobs\BackupTasks\SendTeamsNotificationJob;
use App\Jobs\RunDatabaseBackupTaskJob;
use App\Jobs\RunFileBackupTaskJob;
use App\Mail\BackupTasks\OutputMail;
use App\Traits\HasTags;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use RuntimeException;

class BackupTask extends Model
{
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
     * @return array<string, int>
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
     * @return array<string, int>
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
     * @param  Builder<BackupTask>  $builder
     * @return Builder<BackupTask>
     */
    public function scopeNotPaused(Builder $builder): Builder
    {
        return $builder->whereNull('paused_at');
    }

    /**
     * @param  Builder<BackupTask>  $builder
     * @return Builder<BackupTask>
     */
    public function scopeReady(Builder $builder): Builder
    {
        return $builder->where('status', self::STATUS_READY);
    }

    /**
     * @return BelongsTo<User, BackupTask>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BackupDestination, BackupTask>
     */
    public function backupDestination(): BelongsTo
    {
        return $this->belongsTo(BackupDestination::class);
    }

    /**
     * @return BelongsTo<RemoteServer, BackupTask>
     */
    public function remoteServer(): BelongsTo
    {
        return $this->belongsTo(RemoteServer::class);
    }

    /**
     * @return HasMany<BackupTaskLog>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(BackupTaskLog::class);
    }

    /**
     * @return HasMany<BackupTaskData>
     */
    public function data(): HasMany
    {
        return $this->hasMany(BackupTaskData::class);
    }

    public function updateLastRanAt(): void
    {
        $this->update(['last_run_at' => now()]);
        $this->save();
    }

    public function usingCustomCronExpression(): bool
    {
        return ! is_null($this->custom_cron_expression);
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function markAsRunning(): void
    {
        $this->update(['status' => 'running']);
        $this->save();
    }

    public function markAsReady(): void
    {
        $this->update(['status' => 'ready']);
        $this->save();
    }

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

    public function cronExpressionMatches(): bool
    {
        return $this->cronExpression()->isDue();
    }

    public function isDaily(): bool
    {
        return $this->frequency === self::FREQUENCY_DAILY;
    }

    public function isWeekly(): bool
    {
        return $this->frequency === self::FREQUENCY_WEEKLY;
    }

    public function updateScheduledWeeklyRun(): void
    {
        if (! $this->isWeekly()) {
            return;
        }

        $this->update(['last_scheduled_weekly_run_at' => now()]);
        $this->save();
    }

    public function isRotatingBackups(): bool
    {
        return $this->maximum_backups_to_keep > 0;
    }

    public function run(): void
    {
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

    public function isFilesType(): bool
    {
        return $this->type === self::TYPE_FILES;
    }

    public function isDatabaseType(): bool
    {
        return $this->type === self::TYPE_DATABASE;
    }

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

    public function pause(): void
    {
        $this->update(['paused_at' => now()]);
        $this->save();
    }

    public function resume(): void
    {
        $this->update(['paused_at' => null]);
        $this->save();
    }

    public function isPaused(): bool
    {
        return ! is_null($this->paused_at);
    }

    public function hasFileNameAppended(): bool
    {
        return ! is_null($this->appended_file_name);
    }

    public function setScriptUpdateTime(): void
    {
        $this->update(['last_script_update_at' => now()]);
        $this->saveQuietly();
    }

    public function resetScriptUpdateTime(): void
    {
        $this->update(['last_script_update_at' => null]);
        $this->save();
    }

    public function hasEmailNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_EMAIL)
            ->exists();
    }

    public function hasDiscordNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_DISCORD)
            ->exists();
    }

    public function hasSlackNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_SLACK)
            ->exists();
    }

    public function hasTeamNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_TEAMS)
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

        match ($notificationStream->getAttribute('type')) {
            NotificationStream::TYPE_EMAIL => $this->sendEmailNotification($backupTaskLog, $streamValue),
            NotificationStream::TYPE_DISCORD => SendDiscordNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            NotificationStream::TYPE_SLACK => SendSlackNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            NotificationStream::TYPE_TEAMS => SendTeamsNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            default => throw new InvalidArgumentException("Unsupported notification type: {$notificationStream->getAttribute('type')}"),
        };
    }

    public function sendEmailNotification(BackupTaskLog $backupTaskLog, string $emailAddress): void
    {
        Mail::to($emailAddress)
            ->queue(new OutputMail($backupTaskLog));
    }

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

        if (!$response->successful()) {
            throw new RuntimeException('Teams webhook failed: ' . $response->body());
        }
    }

    public function hasCustomStorePath(): bool
    {
        return ! is_null($this->store_path);
    }

    public function isAnotherTaskRunningOnSameRemoteServer(): bool
    {
        return static::query()
            ->where('remote_server_id', $this->remote_server_id)
            ->where('status', static::STATUS_RUNNING)
            ->where('id', '<>', $this->id)
            ->exists();
    }

    public function listOfAttachedTagLabels(): ?string
    {
        if ($this->tags->isEmpty()) {
            return null;
        }

        return $this->tags->pluck('label')->implode(', ');
    }

    public function hasIsolatedCredentials(): bool
    {
        return $this->getAttribute('isolated_username') !== null && $this->getAttribute('isolated_password') !== null;
    }

    /**
     * Format the backup task's last run time according to their locale preferences.
     */
    public function lastRunFormatted(?User $user = null): string
    {
        if (is_null($this->last_run_at)) {
            return __('Never');
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
    public function runTimeFormatted(?User $user = null): string
    {
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
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'last_run_at' => 'datetime',
            'last_scheduled_weekly_run_at' => 'datetime',
        ];
    }

    /**
     *  Returns the cron expression.
     */
    private function cronExpression(): CronExpression
    {
        return new CronExpression((string) $this->custom_cron_expression);
    }
}
