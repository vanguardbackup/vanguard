<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Laragear\TwoFactor\TwoFactorAuthentication;
use Laravel\Pennant\Concerns\HasFeatures;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Motomedialab\SimpleLaravelAudit\Traits\AuditableModel;

/**
 * Represents a user in the system.
 *
 * This model handles user authentication, profile information, and relationships
 * to various entities such as remote servers, backup destinations, and tasks.
 */
class User extends Authenticatable implements TwoFactorAuthenticatable
{
    use AuditableModel;

    use HasApiTokens;
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasFeatures;
    use Notifiable;
    use TwoFactorAuthentication;

    public const string TWO_FACTOR_APP = 'app';
    public const string TWO_FACTOR_EMAIL = 'email';

    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'preferred_backup_destination_id',
        'language',
        'gravatar_email',
        'weekly_summary_opt_in_at',
        'pagination_count',
        'last_two_factor_at',
        'last_two_factor_ip',
        'two_factor_verified_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Define the model values that shouldn't be audited.
     *
     * @var string[]
     */
    protected array $excludedFromAuditing = [
        'quiet_until',
        'last_two_factor_at',
        'created_at',
        'updated_at',
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
     * Create a new mobile personal access token for the user.
     *
     * @param  string  $name  The name of the token.
     * @param  array<int|string, mixed>  $abilities  The abilities granted to the token. Defaults to all abilities.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the token, if any.
     * @return NewAccessToken The newly created access token.
     */
    public function createMobileToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): NewAccessToken
    {
        $plainTextToken = $this->generateTokenString();

        /** @var PersonalAccessToken $model */
        $model = $this->tokens()->forceCreate([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'mobile_at' => now(),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return new NewAccessToken($model, $model->getKey() . '|' . $plainTextToken);
    }

    /**
     *  The quantity of backup codes the user has consumed.
     */
    public function backupCodesUsedCount(): int
    {
        return $this->getRecoveryCodes()->whereNotNull('used_at')->count();
    }

    /**
     *  The quantity of backup codes the user has left.
     */
    public function backupCodesRemainingCount(): int
    {
        return $this->getRecoveryCodes()->count();
    }

    /**
     * Scope query to users with two-factor auth and outdated backup codes.
     *
     * @param  Builder<User>  $builder
     * @return Builder<User>
     */
    public function scopeWithOutdatedBackupCodes(Builder $builder): Builder
    {
        return $builder->whereHas('twoFactorAuth', function ($subquery): void {
            $subquery->where('recovery_codes_generated_at', '<', now()->subYear());
        });
    }

    /**
     *  Returns whether the user has quiet mode enabled.
     */
    public function hasQuietMode(): bool
    {
        return $this->quiet_until !== null;
    }

    /**
     * Scope query to users that have a quiet mode enabled.
     *
     * @param  Builder<User>  $builder
     * @return Builder<User>
     */
    public function scopeWithQuietMode(Builder $builder): Builder
    {
        return $builder->whereNotNull('quiet_until');
    }

    /**
     *  It clears a user's quiet mode if set.
     */
    public function clearQuietMode(): void
    {
        if (! $this->hasQuietMode()) {
            return;
        }

        $this->forceFill(['quiet_until' => null])->save();
    }

    /**
     * Get the user's external service connections.
     *
     * This relationship retrieves all connections (like GitHub, GitLab)
     * associated with the user.
     *
     * @return HasMany<UserConnection> */
    public function connections(): HasMany
    {
        return $this->hasMany(UserConnection::class);
    }

    /**
     *  Returns whether the user's account has been disabled.
     */
    public function hasDisabledAccount(): bool
    {
        return $this->account_disabled_at !== null;
    }

    /**
     *  It disables the user's account.
     */
    public function disableUserAccount(): bool
    {
        if ($this->hasDisabledAccount()) {
            return false;
        }

        if ($this->isAdmin()) {
            return false;
        }

        return $this->forceFill(['account_disabled_at' => now()])->save();
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
            'last_two_factor_at' => 'datetime',
            'quiet_until' => 'datetime',
            'account_disabled_at' => 'datetime',
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
