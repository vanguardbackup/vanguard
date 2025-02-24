<?php

declare(strict_types=1);

namespace App\Models;

use Override;
use Database\Factories\UserConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a user's connection to an external service like GitHub or GitLab.
 *
 * This model stores authentication details and other relevant information
 * for integrating with third-party services.
 */
class UserConnection extends Model
{
    /** @use HasFactory<UserConnectionFactory> */
    use HasFactory;

    /**
     * Provider name for GitHub connections.
     */
    public const string PROVIDER_GITHUB = 'github';

    /**
     * Provider name for GitLab connections.
     */
    public const string PROVIDER_GITLAB = 'gitlab';

    /**
     * Provider name for Bitbucket connections.
     */
    public const string PROVIDER_BITBUCKET = 'bitbucket';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Get the user that owns the connection.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the connection is for GitHub.
     */
    public function isGitHub(): bool
    {
        return $this->provider_name === self::PROVIDER_GITHUB;
    }

    /**
     * Determine if the connection is for GitLab.
     */
    public function isGitLab(): bool
    {
        return $this->provider_name === self::PROVIDER_GITLAB;
    }

    /**
     * Determine if the connection is for GitLab.
     */
    public function isBitbucket(): bool
    {
        return $this->provider_name === self::PROVIDER_BITBUCKET;
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'scopes' => 'json',
        ];
    }
}
