<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\BackupTasks\SendDiscordNotificationJob;
use App\Jobs\BackupTasks\SendSlackNotificationJob;
use App\Jobs\RunDatabaseBackupTaskJob;
use App\Jobs\RunFileBackupTaskJob;
use App\Mail\BackupTasks\OutputMail;
use App\Traits\HasTags;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BackupTask extends Model
{
    use HasFactory, HasTags;

    const string STATUS_READY = 'ready';
    const string STATUS_RUNNING = 'running';
    const string FREQUENCY_DAILY = 'daily';
    const string FREQUENCY_WEEKLY = 'weekly';
    const string TYPE_FILES = 'files';
    const string TYPE_DATABASE = 'database';

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
            ->selectRaw('COUNT(*) as count, DATE_TRUNC(\'month\', backup_task_data.created_at) as month_date')
            ->groupBy('month_date')
            ->orderBy('month_date')
            ->get();

        return $results->mapWithKeys(function ($item) use ($locale): array {
            $carbonDate = Carbon::parse($item->getAttribute('month_date'))->locale($locale);
            $localizedMonth = ucfirst($carbonDate->isoFormat('MMM YYYY'));

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
            $localizedType = __($item->getAttribute('type'));

            return [$localizedType => $item->getAttribute('count')];
        })
            ->toArray();
    }

    /**
     * @param  Builder<BackupTask>  $query
     * @return Builder<BackupTask>
     */
    public function scopeNotPaused(Builder $query): Builder
    {
        return $query->whereNull('paused_at');
    }

    /**
     * @param  Builder<BackupTask>  $query
     * @return Builder<BackupTask>
     */
    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_READY);
    }

    /**
     * @return BelongsTo<User, \App\Models\BackupTask>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BackupDestination, \App\Models\BackupTask>
     */
    public function backupDestination(): BelongsTo
    {
        return $this->belongsTo(BackupDestination::class);
    }

    /**
     * @return BelongsTo<RemoteServer, \App\Models\BackupTask>
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
            Log::debug("Task {$this->id} is paused, skipping run");

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
            $cron = new CronExpression($this->custom_cron_expression);

            return Carbon::instance($cron->getNextRunDate(Carbon::now()));
        }

        if ($this->frequency === self::FREQUENCY_DAILY) {
            $nextRun = Carbon::today()->setTimeFromTimeString($this->time_to_run_at);

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
                ->setTimeFromTimeString($this->time_to_run_at);
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

    public function hasNotifyEmail(): bool
    {
        return ! is_null($this->notify_email);
    }

    public function hasNotifyDiscordWebhook(): bool
    {
        return ! is_null($this->notify_discord_webhook);
    }

    public function hasNotifySlackWebhook(): bool
    {
        return ! is_null($this->notify_slack_webhook);
    }

    public function sendNotifications(): void
    {
        $latestLog = $this->fresh()?->logs()->latest()->first();
        // if we want to only send notifications on failure in the future ^^

        if ($this->hasNotifyEmail()) {
            $this->sendEmailNotification($latestLog);
        }

        if ($this->hasNotifyDiscordWebhook()) {
            SendDiscordNotificationJob::dispatch($this, $latestLog)
                ->onQueue('backup-task-notifications');
        }

        if ($this->hasNotifySlackWebhook()) {
            SendSlackNotificationJob::dispatch($this, $latestLog)
                ->onQueue('backup-task-notifications');
        }
    }

    public function sendEmailNotification(?BackupTaskLog $latestLog): void
    {
        Mail::to($this->notify_email)
            ->queue(new OutputMail($latestLog));
    }

    public function sendDiscordWebhookNotification(?BackupTaskLog $latestLog): void
    {
        $status = $latestLog?->successful_at ? 'success' : 'failure';
        $message = $latestLog?->successful_at ? 'The backup task was successful. Please see the details below for more information about this task.' : 'The backup task failed. Please see the details below for more information about this task.';
        $color = $latestLog?->successful_at ? 3066993 : 15158332; // Green for success, Red for failure

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
                    'value' => $this->remoteServer?->label,
                    'inline' => true,
                ],
                [
                    'name' => __('Backup Destination'),
                    'value' => $this->backupDestination?->label . ' (' . $this->backupDestination?->type() . ')',
                    'inline' => true,
                ],
                [
                    'name' => __('Result'),
                    'value' => ucfirst($status),
                    'inline' => true,
                ],
                [
                    'name' => __('Ran at'),
                    'value' => $latestLog?->created_at->format('jS F Y, H:i:s'),
                    'inline' => true,
                ],
            ],
            'footer' => [
                'icon_url' => asset('images/logo.png'),
                'text' => __('This notification was sent by :app.', ['app' => config('app.name')]),
            ],
        ];

        $http = Http::withHeaders([
            'Content-Type' => 'application/json',
        ]);

        $http->post($this->notify_discord_webhook, [
            'username' => __('Vanguard'),
            'avatar_url' => asset('images/logo-on-black.png'),
            'embeds' => [$embed],
        ]);
    }

    public function sendSlackWebhookNotification(?BackupTaskLog $latestLog): void
    {
        $status = $latestLog?->successful_at ? 'success' : 'failure';
        $message = $latestLog?->successful_at ? 'The backup task was successful. Please see the details below for more information about this task.' : 'The backup task failed. Please see the details below for more information about this task.';
        $color = $latestLog?->successful_at ? 'good' : 'danger'; // Green for success, Red for failure

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
                            'value' => $latestLog?->created_at->format('jS F Y, H:i:s'),
                            'short' => true,
                        ],
                    ],
                    'footer' => __('This notification was sent by :app.', ['app' => config('app.name')]),
                ],
            ],
        ];

        $http = Http::withHeaders([
            'Content-Type' => 'application/json',
        ]);

        $http->post($this->notify_slack_webhook, $payload);
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

    public function lastRunFormatted(?User $user = null): string
    {
        if (is_null($this->last_run_at)) {
            return __('Never');
        }

        $user = $user ?? Auth::user() ?? new User(['language' => config('app.locale'), 'timezone' => config('app.timezone')]);

        $locale = $user->language === 'dk' ? 'da' : $user->language;

        Carbon::setLocale($locale);

        return Carbon::parse($this->last_run_at)
            ->timezone($user->timezone ?? config('app.timezone'))
            ->locale($locale)
            ->isoFormat('D MMMM YYYY HH:mm');
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

    private function cronExpression(): CronExpression
    {
        return new CronExpression($this->custom_cron_expression);
    }
}
