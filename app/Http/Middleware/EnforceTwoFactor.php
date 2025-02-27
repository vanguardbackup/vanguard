<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

/**
 * Middleware to enforce two-factor authentication.
 */
class EnforceTwoFactor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $this->requiresTwoFactor($user)) {
            return $next($request);
        }

        if ($this->isValidTwoFactorCookie($request, $user) && ! $this->isHighRiskScenario($request, $user)) {
            return $next($request);
        }

        return $this->challengeResponse($request);
    }

    /**
     * Check if the user requires two-factor authentication.
     */
    private function requiresTwoFactor(User $user): bool
    {
        return $user->hasTwoFactorEnabled();
    }

    /**
     * Check if the two-factor cookie is valid for the given user.
     */
    private function isValidTwoFactorCookie(Request $request, User $user): bool
    {
        $cookie = $request->cookie('two_factor_verified');

        if (! $cookie || ! is_string($cookie)) {
            return false;
        }

        return $user->getAttribute('two_factor_verified_token') !== null &&
            Hash::check(decrypt($cookie), $user->getAttribute('two_factor_verified_token'));
    }

    /**
     * Determine if the current scenario is considered high risk.
     */
    private function isHighRiskScenario(Request $request, User $user): bool
    {
        if ($this->isSignificantIpChange($request->ip(), $user->getAttribute('last_two_factor_ip'))) {
            return true;
        }

        return $this->isLastTwoFactorAuthTooOld($user->getAttribute('last_two_factor_at'));
    }

    /**
     * Check if the last two-factor authentication is too old or not set.
     */
    private function isLastTwoFactorAuthTooOld(?Carbon $carbon): bool
    {
        if (! $carbon instanceof Carbon) {
            return true;
        }

        return $carbon->addDays(30)->isPast();
    }

    /**
     * Check if there's a significant change in IP address.
     */
    private function isSignificantIpChange(?string $currentIp, ?string $lastIp): bool
    {
        if (! $currentIp || ! $lastIp) {
            return true;
        }

        $currentIpPrefix = substr($currentIp, 0, strrpos($currentIp, '.') ?: strlen($currentIp));
        $lastIpPrefix = substr($lastIp, 0, strrpos($lastIp, '.') ?: strlen($lastIp));

        return $currentIpPrefix !== $lastIpPrefix;
    }

    /**
     * Generate the appropriate challenge response.
     */
    private function challengeResponse(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse(['message' => 'Two-factor authentication required.'], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('two-factor.challenge');
    }
}
