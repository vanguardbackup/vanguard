<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\CustomMissingAbilityException;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility as SanctumCheckForAnyAbility;
use Override;

class CustomCheckForAnyAbility extends SanctumCheckForAnyAbility
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $request
     * @param  mixed  $next
     * @param  mixed  ...$abilities
     *
     * @throws AuthenticationException
     * @throws CustomMissingAbilityException
     * @throws InvalidArgumentException
     */
    #[Override]
    public function handle($request, $next, ...$abilities): mixed
    {
        $this->validateArguments($request, $next);
        $this->ensureAuthenticated($request);

        $stringAbilities = $this->validateAndFilterAbilities($abilities);

        if ($this->userHasAnyAbility($request, $stringAbilities)) {
            return $next($request);
        }

        throw new CustomMissingAbilityException($stringAbilities);
    }

    /**
     * Validate the incoming arguments.
     *
     *
     * @throws InvalidArgumentException
     */
    private function validateArguments(mixed $request, mixed $next): void
    {
        if (! $request instanceof Request) {
            throw new InvalidArgumentException('$request must be an instance of Illuminate\Http\Request');
        }

        if (! $next instanceof Closure) {
            throw new InvalidArgumentException('$next must be an instance of Closure');
        }
    }

    /**
     * Ensure the user is authenticated.
     *
     *
     * @throws AuthenticationException
     */
    private function ensureAuthenticated(Request $request): void
    {
        if (! $request->user() || ! $request->user()->currentAccessToken()) {
            throw new AuthenticationException('Authentication failed. Please log in.');
        }
    }

    /**
     * Validate and filter the abilities.
     *
     * @param  array<int|string, mixed>  $abilities
     * @return array<int, string>
     *
     * @throws InvalidArgumentException
     */
    private function validateAndFilterAbilities(array $abilities): array
    {
        $stringAbilities = array_values(array_filter($abilities, 'is_string'));

        if (count($stringAbilities) !== count($abilities)) {
            throw new InvalidArgumentException('All abilities must be strings');
        }

        return $stringAbilities;
    }

    /**
     * Check if the user has any of the required abilities.
     *
     * @param  array<int, string>  $abilities
     */
    private function userHasAnyAbility(Request $request, array $abilities): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        foreach ($abilities as $ability) {
            if ($user->tokenCan($ability)) {
                return true;
            }
        }

        return false;
    }
}
