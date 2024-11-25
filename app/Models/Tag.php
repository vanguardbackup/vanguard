<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Motomedialab\SimpleLaravelAudit\Traits\AuditableModel;

/**
 * Represents a tag in the system.
 *
 * This model defines tags that can be associated with various entities
 * through the Taggable model, implementing a polymorphic many-to-many relationship.
 */
class Tag extends Model
{
    use AuditableModel;
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Get the user who created this tag.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
