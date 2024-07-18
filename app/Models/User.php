<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'github_id',
        'preferred_backup_destination_id',
        'language',
        'gravatar_email',
        'weekly_summary_opt_in_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the Gravatar URL for the user.
     */
    public function gravatar(int|float|null $size = 80): string
    {
        $email = $this->gravatar_email ?? $this->email;

        $size = $size > 0 ? (int) $size : 80;
        $sizeQuery = '?s=' . $size;

        return sprintf(
            'https://www.gravatar.com/avatar/%s%s',
            md5(strtolower(trim($email))),
            $sizeQuery
        );
    }

    public function getFirstName(): string
    {
        return explode(' ', $this->name)[0];
    }

    public function getLastName(): string
    {
        $nameParts = explode(' ', $this->name);
        if (count($nameParts) > 1) {
            return end($nameParts);
        }

        return '';
    }

    /**
     * @return HasMany<RemoteServer>
     */
    public function remoteServers(): HasMany
    {
        return $this->hasMany(RemoteServer::class);
    }

    /**
     * @return HasMany<BackupDestination>
     */
    public function backupDestinations(): HasMany
    {
        return $this->hasMany(BackupDestination::class);
    }

    /**
     * @return HasMany<BackupTask>
     */
    public function backupTasks(): HasMany
    {
        return $this->hasMany(BackupTask::class);
    }

    /**
     * @return HasMany<NotificationStream>
     */
    public function notificationStreams(): HasMany
    {
        return $this->hasMany(NotificationStream::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->email, config('auth.admin_email_addresses'), true);
    }

    public function backupTaskLogCount(): int
    {
        return BackupTaskLog::whereHas('backupTask', function ($query): void {
            $query->where('user_id', $this->id);
            $query->whereNotNull('finished_at');
        })->count();
    }

    public function backupTasklogCountToday(): int
    {
        return BackupTaskLog::whereHas('backupTask', function ($query): void {
            $query->where('user_id', $this->id);
        })->whereDate('created_at', today()->timezone($this->timezone ?? 'UTC'))->count();
    }

    public function canLoginWithGithub(): bool
    {
        return $this->github_id !== null;
    }

    /**
     * @return HasMany<Tag>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Determine if the user is opted in to receive weekly summaries.
     */
    public function isOptedInForWeeklySummary(): bool
    {
        return $this->weekly_summary_opt_in_at !== null;
    }

    /**
     *  Scope a query to only include users opted in to receive summary emails.
     *
     * @param  Builder<BackupTask>  $builder
     * @return Builder<BackupTask>
     */
    public function scopeOptedInToReceiveSummaryEmails(Builder $builder): Builder
    {
        return $builder->whereNotNull('weekly_summary_opt_in_at');
    }

    /**
     * Generate backup summary data for the user within a given date range.
     *
     * @param  array<string, Carbon>  $dateRange
     * @return array<string, mixed>
     */
    public function generateBackupSummaryData(array $dateRange): array
    {
        $backupTasks = $this->backupTasks()
            ->with(['logs' => function ($query) use ($dateRange): void {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }])
            ->whereHas('logs', function ($query) use ($dateRange): void {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            })
            ->get();

        $totalTasks = $backupTasks->flatMap->logs->count();
        $successfulTasks = $backupTasks->flatMap->logs->whereNotNull('successful_at')->count();
        $failedTasks = $totalTasks - $successfulTasks;

        return [
            'total_tasks' => $totalTasks,
            'successful_tasks' => $successfulTasks,
            'failed_tasks' => $failedTasks,
            'success_rate' => $totalTasks > 0 ? ($successfulTasks / $totalTasks) * 100 : 0,
            'date_range' => [
                'start' => $dateRange['start']->toDateString(),
                'end' => $dateRange['end']->toDateString(),
            ],
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function firstName(): Attribute
    {
        return new Attribute(
            get: fn (): string => $this->getFirstName(),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function lastName(): Attribute
    {
        return new Attribute(
            get: fn (): string => $this->getLastName(),
        );
    }

    /**
     * Get the casts array.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'weekly_summary_opt_in_at' => 'datetime',
        ];
    }
}
