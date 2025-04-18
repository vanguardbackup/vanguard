<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\RunDatabaseBackupTaskJob;
use App\Jobs\RunFileBackupTaskJob;
use App\Models\Traits\ComposesTelegramNotification;
use App\Models\Traits\HasNotificationStream;
use App\Models\Traits\NotificationStreamable;
use App\Traits\HasTags;
use Carbon\CarbonInterface;
use Cron\CronExpression;
use Database\Factories\BackupTaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Motomedialab\SimpleLaravelAudit\Traits\AuditableModel;
use Number;
use Override;

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
    // Contains all the Notification Stream Destinations
    use HasNotificationStream;
    use HasTags;
    use NotificationStreamable; // Contains all the 'HasEmail' methods, put in here for readability!

    public const string STATUS_READY = 'ready';
    public const string STATUS_RUNNING = 'running';
    public const string FREQUENCY_DAILY = 'daily';
    public const string FREQUENCY_WEEKLY = 'weekly';
    public const string TYPE_FILES = 'files';
    public const string TYPE_DATABASE = 'database';

    protected $guarded = [];

    protected $hidden = [
        'webhook_token',
    ];

    /**
     * Define the model values that shouldn't be audited.
     *
     * @var string[]
     */
    protected array $excludedFromAuditing = [
        'status',
        'created_at',
        'updated_at',
        'run_webhook_last_used_at',
        'webhook_token',
        'favourited_at',
        'paused_at',
    ];

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
     * Get backup tasks size data by task type for radar chart
     *
     * @param  int|null  $userId  The ID of the user, or null for all users
     * @param  int  $limit  The number of latest backup tasks to consider
     * @return array<string, mixed> An array containing labels and size data for radar chart
     */
    public static function backupSizeByTypeData(?int $userId = null, int $limit = 100): array
    {
        $builder = BackupTaskData::query()
            ->select('backup_task_data.backup_task_id')
            ->selectRaw('MAX(backup_task_data.created_at) as latest_date')
            ->whereNotNull('backup_task_data.size')
            ->where('backup_task_data.size', '>', 0)
            ->groupBy('backup_task_data.backup_task_id');

        if ($userId !== null) {
            $builder->join('backup_tasks', 'backup_tasks.id', '=', 'backup_task_data.backup_task_id')
                ->where('backup_tasks.user_id', $userId);
        }

        $latestBackups = $builder->orderBy('latest_date', 'desc')
            ->limit($limit)
            ->get();

        $backupTaskIds = $latestBackups->pluck('backup_task_id')->toArray();

        if (empty($backupTaskIds)) {
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => __('Average Backup Size'),
                        'data' => [],
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgb(54, 162, 235)',
                        'pointBackgroundColor' => 'rgb(54, 162, 235)',
                    ],
                ],
                'formatted' => [],
            ];
        }

        $sizeData = BackupTaskData::query()
            ->join('backup_tasks', 'backup_tasks.id', '=', 'backup_task_data.backup_task_id')
            ->whereIn('backup_task_data.backup_task_id', $backupTaskIds)
            ->whereNotNull('backup_task_data.size')
            ->where('backup_task_data.size', '>', 0)
            ->select('backup_tasks.id', 'backup_tasks.label', 'backup_tasks.type')
            ->selectRaw('AVG(backup_task_data.size) as average_size')
            ->groupBy('backup_tasks.id', 'backup_tasks.label', 'backup_tasks.type')
            ->get();

        $result = [];
        foreach ($sizeData as $data) {
            $taskLabel = $data['label'];
            $size = (int) $data['average_size'];

            $result[$taskLabel] = [
                'type' => $data['type'],
                'average_size' => $size,
                'formatted_size' => self::formatFileSize($size),
            ];
        }

        return [
            'labels' => array_keys($result),
            'datasets' => [
                [
                    'label' => __('Average Backup Size'),
                    'data' => array_values(array_map(fn ($item): int => $item['average_size'], $result)),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'pointBackgroundColor' => 'rgb(54, 162, 235)',
                ],
            ],
            'formatted' => array_values(array_map(fn ($item): string => $item['formatted_size'], $result)),
        ];
    }

    /**
     * Boot the model.
     */
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // Generate tokens for new models during creation
        static::creating(function (BackupTask $backupTask): void {
            if (empty($backupTask->getAttribute('webhook_token'))) {
                $backupTask->setAttribute('webhook_token', static::generateUniqueToken());
            }
        });

        // Ensure all retrieved models have webhook tokens
        static::retrieved(function (BackupTask $backupTask): void {
            if (empty($backupTask->getAttribute('webhook_token'))) {
                $backupTask->setAttribute('webhook_token', static::generateUniqueToken());
                $backupTask->save();
            }
        });
    }

    /**
     * Generate a unique token that doesn't conflict with existing ones.
     */
    protected static function generateUniqueToken(): string
    {
        $token = Str::random(64);

        while (self::where('webhook_token', $token)->exists()) {
            // Generate a new token if there's a collision
            $token = Str::random(64);
        }

        return $token;
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the backup destination for this task.
     *
     * @return BelongsTo<BackupDestination, $this>
     */
    public function backupDestination(): BelongsTo
    {
        return $this->belongsTo(BackupDestination::class);
    }

    /**
     * Get the remote server for this task.
     *
     * @return BelongsTo<RemoteServer, $this>
     */
    public function remoteServer(): BelongsTo
    {
        return $this->belongsTo(RemoteServer::class);
    }

    /**
     * Get the logs for this backup task.
     *
     * @return HasMany<BackupTaskLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(BackupTaskLog::class);
    }

    /**
     * Get the data associated with this backup task.
     *
     * @return HasMany<BackupTaskData, $this>
     */
    public function data(): HasMany
    {
        return $this->hasMany(BackupTaskData::class);
    }

    /**
     * Get the scripts associated with this backup task.
     *
     * @return BelongsToMany<Script, $this>
     */
    public function scripts(): BelongsToMany
    {
        return $this->belongsToMany(Script::class, 'backup_task_script');
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
        if ($this->user?->hasSuspendedAccount()) {
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
     * Check if the task is favourited.
     */
    public function isFavourited(): bool
    {
        return ! is_null($this->favourited_at);
    }

    /**
     * Favour the backup task.
     */
    public function favourite(): void
    {
        $this->update(['favourited_at' => now()]);
        $this->save();
    }

    /**
     * Unfavour the backup task.
     */
    public function unfavourite(): void
    {
        $this->update(['favourited_at' => null]);
        $this->save();
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
     * Check if the task has an encryption password set.
     */
    public function hasEncryptionPassword(): bool
    {
        return $this->getAttribute('encryption_password') !== null;
    }

    /**
     * Check if the task has any pre-scripts configured.
     */
    public function hasPrescript(): bool
    {
        return $this->scripts()->where('type', Script::TYPE_PRESCRIPT)->exists();
    }

    /**
     * Check if the task has any post-scripts configured.
     */
    public function hasPostscript(): bool
    {
        return $this->scripts()->where('type', Script::TYPE_POSTSCRIPT)->exists();
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
     * Refresh the webhook token.
     */
    public function refreshWebhookToken(): string
    {
        $this->setAttribute('webhook_token', static::generateUniqueToken());
        $this->save();

        return $this->getAttribute('webhook_token');
    }

    /**
     * Updates the timestamp for when the 'run' webhook was last executed.
     */
    public function setRunWebhookTime(): void
    {
        $this->update(['run_webhook_last_used_at' => now()]);
        $this->saveQuietly();
    }

    /**
     * Get the casts array for the model's attributes.
     *
     * @return string[]
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'last_run_at' => 'datetime',
            'last_scheduled_weekly_run_at' => 'datetime',
            'encryption_password' => 'encrypted',
            'run_webhook_last_used_at' => 'datetime',
        ];
    }

    /**
     * Get the webhook URL for this backup task.
     *
     * @return Attribute<string, never>
     */
    protected function webhookUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->generateWebhookUrl(),
        );
    }

    protected function generateWebhookUrl(): string
    {
        if (empty($this->getAttribute('webhook_token'))) {
            $this->setAttribute('webhook_token', static::generateUniqueToken());
            $this->save();
        }

        return route('webhooks.backup-tasks.run', [
            'backupTask' => $this,
            'token' => $this->getAttribute('webhook_token'),
        ]);
    }

    /**
     * Returns the cron expression for the task.
     */
    private function cronExpression(): CronExpression
    {
        return new CronExpression((string) $this->custom_cron_expression);
    }
}
