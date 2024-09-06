<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserDismissalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a user's dismissal or completion of various features or guides.
 *
 * This model can be used to track when a user has dismissed a feature,
 * completed an intro guide, or any similar action that should be remembered.
 *
 * @method static Builder|UserDismissal ofType(string $type)
 * @method static UserDismissalFactory factory(...$parameters)
 */
class UserDismissal extends Model
{
    /** @use HasFactory<UserDismissalFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'dismissable_type',
        'dismissable_id',
        'dismissed_at',
    ];

    /**
     * Check if a specific item has been dismissed by the user.
     */
    public static function isDismissed(int $userId, string $type, string|int $id): bool
    {
        return static::where('user_id', $userId)
            ->where('dismissable_type', $type)
            ->where('dismissable_id', $id)
            ->exists();
    }

    /**
     * Dismiss a specific item for a user.
     */
    public static function dismiss(int $userId, string $type, string|int $id): static
    {
        /** @var static $dismissal */
        $dismissal = static::create([
            'user_id' => $userId,
            'dismissable_type' => $type,
            'dismissable_id' => $id,
            'dismissed_at' => now(),
        ]);

        return $dismissal;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserDismissalFactory
    {
        return UserDismissalFactory::new();
    }

    /**
     * Get the user that owns the dismissal.
     *
     * @return BelongsTo<User, UserDismissal>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include dismissals of a specific type.
     *
     * @param  Builder<UserDismissal>  $builder
     * @return Builder<UserDismissal>
     */
    public function scopeOfType(Builder $builder, string $type): Builder
    {
        return $builder->where('dismissable_type', $type);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }
}
