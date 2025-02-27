<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

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

        Log::debug('Two-factor check', [
            'user_id' => $user->getAttribute('uuid'),
            'has_cookie' => (bool) $request->cookie('two_factor_verified'),
            'last_2fa_at' => $user->getAttribute('last_two_factor_at'),
        ]);

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

        try {
            $decrypted = decrypt($cookie);

            return $user->getAttribute('two_factor_verified_token') !== null &&
                Hash::check($decrypted, $user->getAttribute('two_factor_verified_token'));
        } catch (Exception $e) {
            Log::warning('Failed to decrypt two-factor cookie', [
                'user_id' => $user->getAttribute('uuid'),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Determine if the current scenario is considered high risk.
     */
    private function isHighRiskScenario(Request $request, User $user): bool
    {
        if ($request->ip() !== $user->getAttribute('last_two_factor_ip') &&
            $this->isSignificantlyDifferentIp($request->ip(), $user->getAttribute('last_two_factor_ip'))) {
            Log::debug('IP change detected', [
                'current' => $request->ip(),
                'previous' => $user->getAttribute('last_two_factor_ip'),
            ]);

            return true;
        }

        return $this->isLastTwoFactorAuthTooOld($user->getAttribute('last_two_factor_at'), 60);
    }

    /**
     * Check if the last two-factor authentication is too old or not set.
     */
    private function isLastTwoFactorAuthTooOld(?Carbon $carbon, int $days = 30): bool
    {
        if (! $carbon instanceof Carbon) {
            return true;
        }

        return $carbon->addDays($days)->isPast();
    }

    /**
     * Check if there's a significant change in IP address.
     * This implementation is less strict than before, considering only completely different IPs.
     */
    private function isSignificantlyDifferentIp(?string $currentIp, ?string $lastIp): bool
    {
        if (! $currentIp || ! $lastIp) {
            return true;
        }

        // More lenient IP check - only first two octets for broader subnet matching
        $currentIpPrefix = implode('.', array_slice(explode('.', $currentIp), 0, 2));
        $lastIpPrefix = implode('.', array_slice(explode('.', $lastIp), 0, 2));

        return $currentIpPrefix !== $lastIpPrefix;
    }

    /**
     * Generate the appropriate challenge response.
     */
    private function challengeResponse(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse(['message' => 'Two-factor authentication required.'], ResponseAlias::HTTP_FORBIDDEN);
        }

        return Redirect::route('two-factor.challenge');
    }
}
