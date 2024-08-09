<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\Exceptions\MissingAbilityException;

class CustomMissingAbilityException extends MissingAbilityException
{
    /**
     * The abilities that the user does not have.
     *
     * @var array<int, string>
     */
    protected $abilities;

    /**
     * Create a new exception instance.
     *
     * @param  array<int, string>|string  $abilities
     */
    public function __construct(array|string $abilities = [])
    {
        parent::__construct($abilities);
        $this->abilities = is_string($abilities) ? [$abilities] : $abilities;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $message = 'Access denied due to insufficient permissions. ';
        $message .= 'Required token ability scopes: ' . implode(', ', $this->abilities);

        return response()->json(['message' => $message], 403);
    }
}
