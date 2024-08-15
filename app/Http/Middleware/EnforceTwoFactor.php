<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnforceTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->requiresTwoFactor($user)) {
            return $next($request);
        }

        if ($this->isValidTwoFactorCookie($request, $user) && ! $this->isHighRiskScenario($request, $user)) {
            return $next($request);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Two-factor authentication required.'], Response::HTTP_FORBIDDEN)
            : redirect()->route('two-factor.challenge');
    }

    /**
     * Determine if the user requires two-factor authentication.
     */
    private function requiresTwoFactor(mixed $user): bool
    {
        return $user->hasTwoFactorEnabled();
    }

    /**
     * Check if the two-factor cookie is valid.
     */
    private function isValidTwoFactorCookie(Request $request, mixed $user): bool
    {
        $cookie = $request->cookie('two_factor_verified');

        if (! $cookie || ! is_string($cookie)) {
            return false;
        }

        return Hash::check(decrypt($cookie), $user->two_factor_verified_token);
    }

    /**
     * Check if the current request is a high-risk scenario.
     */
    private function isHighRiskScenario(Request $request, mixed $user): bool
    {
        if ($this->isSignificantIpChange($request->ip(), $user->last_two_factor_ip)) {
            return true;
        }

        return (bool) $user->last_two_factor_at->addDays(30)->isPast();
    }

    /**
     * Determine if there's a significant change in IP address.
     */
    private function isSignificantIpChange(?string $currentIp, ?string $lastIp): bool
    {
        if (! $currentIp || ! $lastIp) {
            return false;
        }

        $currentIpPrefix = substr($currentIp, 0, strrpos($currentIp, '.') ?: strlen($currentIp));
        $lastIpPrefix = substr($lastIp, 0, strrpos($lastIp, '.') ?: strlen($lastIp));

        return $currentIpPrefix !== $lastIpPrefix;
    }
}
