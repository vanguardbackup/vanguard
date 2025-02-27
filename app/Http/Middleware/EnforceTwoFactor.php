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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Middleware to enforce two-factor authentication.
 */
class EnforceTwoFactor
{
    /**
     * The number of days before requiring a new 2FA verification.
     */
    private const int TWO_FACTOR_EXPIRY_DAYS = 30;

    /**
     * The name of the cookie that stores the 2FA verification status.
     */
    private const string TWO_FACTOR_COOKIE = 'two_factor_verified';

    /**
     * The name of the session key that indicates a 2FA bypass.
     */
    private const string TWO_FACTOR_BYPASS_SESSION = 'two_factor_bypass';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (! $user || ! $this->requiresTwoFactor($user)) {
            return $next($request);
        }

        if (Session::get(self::TWO_FACTOR_BYPASS_SESSION) === true) {
            return $next($request);
        }

        if ($this->isVerificationValid($request, $user)) {
            // Mark this session as not requiring 2FA verification temporarily
            // This helps with the multiple tabs issue
            Session::put(self::TWO_FACTOR_BYPASS_SESSION, true);

            return $next($request);
        }

        $this->logTwoFactorChallengeReason($request, $user);

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
     * Check if the current 2FA verification is valid.
     */
    private function isVerificationValid(Request $request, User $user): bool
    {
        return $this->isValidTwoFactorCookie($request, $user) &&
            ! $this->isHighRiskScenario($request, $user);
    }

    /**
     * Check if the two-factor cookie is valid for the given user.
     */
    private function isValidTwoFactorCookie(Request $request, User $user): bool
    {
        $cookie = $request->cookie(self::TWO_FACTOR_COOKIE);

        if (! $cookie || ! is_string($cookie)) {
            return false;
        }

        try {
            $decryptedCookie = decrypt($cookie, false);
            $verifiedToken = $user->getAttribute('two_factor_verified_token');

            return $verifiedToken !== null && Hash::check($decryptedCookie, $verifiedToken);
        } catch (Exception $e) {
            Log::warning('Two-factor cookie decryption failed', [
                'user_id' => $user->getKey(),
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
        $lastTwoFactorIp = $user->getAttribute('last_two_factor_ip');
        $lastTwoFactorAt = $user->getAttribute('last_two_factor_at');
        $currentIp = $request->ip();

        if ($this->isSignificantIpChange($currentIp, $lastTwoFactorIp) &&
            $this->isOlderThan($lastTwoFactorAt, 7)) {
            return true;
        }

        return $this->isOlderThan($lastTwoFactorAt, self::TWO_FACTOR_EXPIRY_DAYS);
    }

    /**
     * Check if a carbon date is older than the specified number of days or not set.
     */
    private function isOlderThan(?Carbon $date, int $days): bool
    {
        if (! $date instanceof Carbon) {
            return true;
        }

        return $date->addDays($days)->isPast();
    }

    /**
     * Check if there's a significant change in IP address.
     * More sophisticated than just prefix matching.
     */
    private function isSignificantIpChange(?string $currentIp, ?string $lastIp): bool
    {
        if (! $currentIp || ! $lastIp) {
            return true;
        }

        // If exact IP match, no significant change
        if ($currentIp === $lastIp) {
            return false;
        }

        // For IPv4, compare the first two octets (Class B network)
        // This is less strict than comparing the first three octets
        if (filter_var($currentIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
            filter_var($lastIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {

            $currentParts = explode('.', $currentIp);
            $lastParts = explode('.', $lastIp);

            if (count($currentParts) === 4 && count($lastParts) === 4) {
                // Compare only the first two octets (Class B network)
                return $currentParts[0] !== $lastParts[0] || $currentParts[1] !== $lastParts[1];
            }
        }

        // For IPv6 or mixed protocols, be more lenient
        // Only consider it a significant change if IPs are completely different

        return true;
    }

    /**
     * Log the reason why a 2FA challenge was triggered, for debugging purposes.
     */
    private function logTwoFactorChallengeReason(Request $request, User $user): void
    {
        $reasons = [];

        if (! $this->isValidTwoFactorCookie($request, $user)) {
            $reasons[] = 'Invalid or missing 2FA cookie';
        }

        $lastTwoFactorAt = $user->getAttribute('last_two_factor_at');
        if ($this->isOlderThan($lastTwoFactorAt, self::TWO_FACTOR_EXPIRY_DAYS)) {
            $reasons[] = 'Last 2FA verification expired (over ' . self::TWO_FACTOR_EXPIRY_DAYS . ' days old)';
        }

        $lastTwoFactorIp = $user->getAttribute('last_two_factor_ip');
        if ($this->isSignificantIpChange($request->ip(), $lastTwoFactorIp)) {
            $reasons[] = 'Significant IP change detected';
        }

        Log::debug('Two-factor authentication challenge triggered', [
            'user_id' => $user->getKey(),
            'reasons' => $reasons,
            'current_ip' => $request->ip(),
            'last_ip' => $lastTwoFactorIp,
            'last_verification' => $lastTwoFactorAt ? $lastTwoFactorAt->toDateTimeString() : null,
        ]);
    }

    /**
     * Generate the appropriate challenge response.
     */
    private function challengeResponse(Request $request): JsonResponse|RedirectResponse
    {
        Cookie::queue(Cookie::forget(self::TWO_FACTOR_COOKIE));

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => 'Two-factor authentication required.',
                'code' => 'two_factor_required',
            ], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('two-factor.challenge');
    }
}
