<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\PersonalAccessTokenFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Override;

/**
 * Represents a personal access token.
 *
 * This model extends Sanctum's personal access token, allowing for mobile token
 * checks and identification of soon-to-expire tokens.
 *
 * @property Carbon|null $mobile_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $last_notification_sent_at
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /** @use HasFactory<PersonalAccessTokenFactory> */
    use HasFactory;

    // Number of days before expiration to start sending notifications
    private const int NOTIFICATION_START_DAYS = 3;
    // Number of days between notifications
    private const int NOTIFICATION_INTERVAL_DAYS = 1;

    /** @var string The table associated with the model. */
    protected $table = 'personal_access_tokens';

    /**
     * Determine whether the token is a mobile token.
     */
    public function isMobileToken(): bool
    {
        return (bool) $this->getAttribute('mobile_at');
    }

    /**
     * Scope a query to only include non-expired tokens expiring within the next 3 days.
     *
     * @param  Builder<PersonalAccessToken>  $builder
     * @return Builder<PersonalAccessToken>
     */
    public function scopeExpiringWithinThreeDays(Builder $builder): Builder
    {
        $now = Carbon::now();
        $threeDaysFromNow = $now->copy()->addDays(self::NOTIFICATION_START_DAYS);

        return $builder->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $threeDaysFromNow);
    }

    /**
     * Scope a query to include tokens that need notifications.
     *
     * @param  Builder<PersonalAccessToken>  $builder
     * @return Builder<PersonalAccessToken>
     */
    public function scopeNeedingNotification(Builder $builder): Builder
    {
        $now = now();

        return $builder->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $now->copy()->addDays(self::NOTIFICATION_START_DAYS))
            ->where(function (Builder $builder) use ($now): void {
                $builder->whereNull('last_notification_sent_at')
                    ->orWhere('last_notification_sent_at', '<', $now->copy()->subDays(self::NOTIFICATION_INTERVAL_DAYS));
            });
    }

    /**
     * Get the casts array for the model's attributes.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'mobile_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_notification_sent_at' => 'datetime',
        ];
    }
}
