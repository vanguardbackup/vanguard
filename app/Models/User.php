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

/**
 * Represents a user in the system.
 *
 * This model handles user authentication, profile information, and relationships
 * to various entities such as remote servers, backup destinations, and tasks.
 */
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
     * Get the user's remote servers.
     *
     * @return HasMany<RemoteServer>
     */
    public function remoteServers(): HasMany
    {
        return $this->hasMany(RemoteServer::class);
    }

    /**
     * Get the user's backup destinations.
     *
     * @return HasMany<BackupDestination>
     */
    public function backupDestinations(): HasMany
    {
        return $this->hasMany(BackupDestination::class);
    }

    /**
     * Get the user's backup tasks.
     *
     * @return HasMany<BackupTask>
     */
    public function backupTasks(): HasMany
    {
        return $this->hasMany(BackupTask::class);
    }

    /**
     * Get the user's notification streams.
     *
     * @return HasMany<NotificationStream>
     */
    public function notificationStreams(): HasMany
    {
        return $this->hasMany(NotificationStream::class);
    }

    /**
     * Get the user's tags.
     *
     * @return HasMany<Tag>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Get the Gravatar URL for the user.
     */
    public function gravatar(int|float|null $size = 80): string
    {
        $email = $this->gravatar_email ?? $this->email;
        $size = $size > 0 ? (int) $size : 80;

        return sprintf(
            'https://www.gravatar.com/avatar/%s?s=%d',
            md5(strtolower(trim($email))),
            $size
        );
    }

    /**
     * Get the user's first name.
     */
    public function getFirstName(): string
    {
        return explode(' ', $this->name)[0];
    }

    /**
     * Get the user's last name.
     */
    public function getLastName(): string
    {
        $nameParts = explode(' ', $this->name);

        return count($nameParts) > 1 ? end($nameParts) : '';
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return in_array($this->email, config('auth.admin_email_addresses'), true);
    }

    /**
     * Get the total count of backup task logs for the user.
     */
    public function backupTaskLogCount(): int
    {
        return BackupTaskLog::whereHas('backupTask', function ($query): void {
            $query->where('user_id', $this->id)->whereNotNull('finished_at');
        })->count();
    }

    /**
     * Get the count of backup task logs for the user today.
     */
    public function backupTasklogCountToday(): int
    {
        return BackupTaskLog::whereHas('backupTask', function ($query): void {
            $query->where('user_id', $this->id);
        })->whereDate('created_at', today()->timezone($this->timezone ?? 'UTC'))->count();
    }

    /**
     * Check if the user can log in with GitHub.
     */
    public function canLoginWithGithub(): bool
    {
        return $this->github_id !== null;
    }

    /**
     * Determine if the user is opted in to receive weekly summaries.
     */
    public function isOptedInForWeeklySummary(): bool
    {
        return $this->weekly_summary_opt_in_at !== null;
    }

    /**
     * Scope a query to only include users opted in to receive summary emails.
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

    /**
     * Get the user's first name as an attribute.
     *
     * @return Attribute<string, never>
     */
    protected function firstName(): Attribute
    {
        return Attribute::get(fn (): string => $this->getFirstName());
    }

    /**
     * Get the user's last name as an attribute.
     *
     * @return Attribute<string, never>
     */
    protected function lastName(): Attribute
    {
        return Attribute::get(fn (): string => $this->getLastName());
    }
}
