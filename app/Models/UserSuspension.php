<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\UserSuspensionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class UserSuspension extends Model
{
    /** @use HasFactory<UserSuspensionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     *  The user relating to the suspension instance.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     *  The administrator who lifted the suspension.
     *
     * @return BelongsTo<User, $this>
     */
    public function liftedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by_admin_user_id');
    }

    /**
     * The administrator who performed the suspension.
     *
     * @return BelongsTo<User, $this>
     */
    public function suspendedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Scope a query to only include active suspensions.
     * A suspension is active if:
     * 1. It has been applied (suspended_at is not null)
     * 2. It has not been lifted (lifted_at is null)
     * 3. The suspension end date (suspended_until) is either null (indefinite) or in the future
     *
     * @param  Builder<UserSuspension>  $builder
     * @return Builder<UserSuspension>
     */
    public function scopeActive(Builder $builder): Builder
    {
        return $builder->whereNotNull('suspended_at')
            ->whereNull('lifted_at')
            ->where(function (Builder $builder): void {
                $builder->whereNull('suspended_until')
                    ->orWhere('suspended_until', '>', Carbon::now());
            });
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
            'suspended_until' => 'datetime',
            'lifted_at' => 'datetime',
        ];
    }
}
