<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserSuspensionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSuspension extends Model
{
    /** @use HasFactory<UserSuspensionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     *  The user relating to the suspension instance.
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
