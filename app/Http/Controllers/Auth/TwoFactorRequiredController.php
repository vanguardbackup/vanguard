<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TwoFactorRequiredController extends Controller
{
    /**
     * Handle the two-factor authentication challenge.
     *
     * This method serves both GET and POST requests:
     * - GET: Display the two-factor challenge view.
     * - POST: Verify the submitted two-factor code.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View|RedirectResponse Returns a view for GET requests or a redirect for POST requests
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        return $request->isMethod('post')
            ? $this->verifyTwoFactor($request)
            : view('auth.two-factor-challenge');
    }

    /**
     * Verify the submitted two-factor authentication code.
     *
     * This method handles the validation of the two-factor code, implements
     * rate limiting, and sets up the necessary cookies and user data upon
     * successful verification.
     *
     * @param  Request  $request  The incoming HTTP request containing the two-factor code
     * @return RedirectResponse A redirect response based on the verification result
     */
    private function verifyTwoFactor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if (! $user) {
            return back()->withErrors(['code' => 'User not authenticated.']);
        }

        if ($this->isRateLimited($user->id)) {
            return $this->rateLimitedResponse();
        }

        if ($user->validateTwoFactorCode($validated['code'])) {
            return $this->handleSuccessfulVerification($user);
        }

        return $this->handleFailedVerification($user->id);
    }

    /**
     * Generate a secure token for two-factor verification.
     *
     * @param  int  $userId  The ID of the user for whom the token is being generated
     * @return string A secure, unique token
     */
    private function generateSecureToken(int $userId): string
    {
        return hash_hmac('sha256', $userId . uniqid('', true), (string) config('app.key'));
    }

    /**
     * Check if the two-factor attempts are rate limited for the given user.
     *
     * @param  int  $userId  The ID of the user to check
     * @return bool True if rate limited, false otherwise
     */
    private function isRateLimited(int $userId): bool
    {
        return RateLimiter::tooManyAttempts($this->getRateLimitKey($userId), 5);
    }

    /**
     * Get the rate limit key for a given user ID.
     *
     * @param  int  $userId  The ID of the user
     * @return string The rate limit key
     */
    private function getRateLimitKey(int $userId): string
    {
        return 'two-factor-attempt:' . $userId;
    }

    /**
     * Handle a successful two-factor verification.
     *
     * @param  mixed  $user  The authenticated user
     * @return RedirectResponse A redirect to the intended route
     */
    private function handleSuccessfulVerification(mixed $user): RedirectResponse
    {
        RateLimiter::clear($this->getRateLimitKey($user->id));
        $token = $this->generateSecureToken($user->id);

        Cookie::queue('two_factor_verified', encrypt($token), 30 * 24 * 60, null, null, true, true, false, 'strict');

        $user->update([
            'two_factor_verified_token' => Hash::make($token),
            'last_two_factor_at' => now(),
            'last_two_factor_ip' => request()->ip(),
        ]);

        return redirect()->intended(route('overview'));
    }

    /**
     * Handle a failed two-factor verification attempt.
     *
     * @param  int  $userId  The ID of the user who failed the verification
     * @return RedirectResponse A redirect back with an error message
     */
    private function handleFailedVerification(int $userId): RedirectResponse
    {
        RateLimiter::hit($this->getRateLimitKey($userId));
        sleep(random_int(1, 3)); // Mitigate timing attacks

        return back()->withErrors(['code' => 'The provided two-factor code was invalid.']);
    }

    /**
     * Generate a response for when the user has been rate limited.
     *
     * @return RedirectResponse A redirect back with a rate limit error message
     */
    private function rateLimitedResponse(): RedirectResponse
    {
        $user = request()->user();

        if (! $user) {
            return back()->withErrors(['code' => 'User not authenticated.']);
        }

        $seconds = RateLimiter::availableIn($this->getRateLimitKey($user->id));

        return back()->withErrors(['code' => "Too many attempts. Please try again in {$seconds} seconds."]);
    }
}
