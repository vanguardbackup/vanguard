<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Represents a personal access token.
 *
 * This model extends Sanctum's personal access token and allows for checking
 * if the token is a mobile token.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    /**
     *  Determine whether the token is a mobile token or not.
     */
    public function isMobileToken(): bool
    {
        return (bool) $this->getAttribute('mobile_at');
    }

    /**
     * Get the casts array for the model's attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mobile_at' => 'bool',
        ];
    }
}
