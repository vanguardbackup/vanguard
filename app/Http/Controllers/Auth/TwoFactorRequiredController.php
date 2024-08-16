<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\User\TwoFactor\BackupCodeConsumedMail;
use App\Mail\User\TwoFactor\LowBackupCodesNoticeMail;
use App\Mail\User\TwoFactor\NoBackupCodesRemainingNoticeMail;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorRequiredController extends Controller
{
    /**
     * Handle the two-factor authentication challenge.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('overview');
        }

        if ($this->hasValidTwoFactorCookie($request, $user)) {
            return redirect()->route('overview');
        }

        return $request->isMethod('post')
            ? $this->verifyTwoFactor($request)
            : view('auth.two-factor-challenge');
    }

    /**
     * Check if the request has a valid two-factor cookie.
     */
    private function hasValidTwoFactorCookie(Request $request, mixed $user): bool
    {
        $twoFactorCookie = $request->cookie('two_factor_verified');

        if (! is_string($twoFactorCookie)) {
            return false;
        }

        try {
            $decryptedToken = decrypt($twoFactorCookie);

            return Hash::check($decryptedToken, $user->getAttribute('two_factor_verified_token'));
        } catch (DecryptException) {
            return false;
        }
    }

    /**
     * Verify the submitted two-factor authentication code.
     */
    private function verifyTwoFactor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user) {
            return back()->withErrors(['code' => 'User not authenticated.']);
        }

        if ($this->isRateLimited($user->id)) {
            return $this->rateLimitedResponse($user->id);
        }

        return $user->validateTwoFactorCode($validated['code'])
            ? $this->handleSuccessfulVerification($user)
            : $this->handleFailedVerification($user->id);
    }

    /**
     * Generate a secure token for two-factor verification.
     */
    private function generateSecureToken(int $userId): string
    {
        return hash_hmac('sha256', $userId . uniqid('', true), (string) config('app.key'));
    }

    /**
     * Check if the two-factor attempts are rate limited for the given user.
     */
    private function isRateLimited(int $userId): bool
    {
        return RateLimiter::tooManyAttempts($this->getRateLimitKey($userId), 5);
    }

    /**
     * Get the rate limit key for a given user ID.
     */
    private function getRateLimitKey(int $userId): string
    {
        return "two-factor-attempt:{$userId}";
    }

    /**
     * Handle a successful two-factor verification.
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

        $unusedCodeCount = $this->getUnusedRecoveryCodeCount($user);

        if ($this->wasRecoveryCodeUsed($user, request('code'))) {
            Mail::to($user)->queue(new BackupCodeConsumedMail($user));
        }

        if ($unusedCodeCount === 0) {
            Mail::to($user)->queue(new NoBackupCodesRemainingNoticeMail($user));

            return $this->redirectWithWarning('You have no unused recovery codes left. Please generate new ones immediately.');
        }

        if ($unusedCodeCount <= 3) {
            Mail::to($user)->queue(new LowBackupCodesNoticeMail($user));

            return $this->redirectWithWarning("You only have {$unusedCodeCount} unused recovery codes left. Consider generating new ones.");
        }

        return redirect()->intended(route('overview'));
    }

    /**
     * Get the count of unused recovery codes.
     */
    private function getUnusedRecoveryCodeCount(mixed $user): int
    {
        $recoveryCodes = $user->getRecoveryCodes();

        return $recoveryCodes->filter(fn ($code): bool => $code['used_at'] === null)->count();
    }

    /**
     * Redirect with a warning message.
     */
    private function redirectWithWarning(string $message): RedirectResponse
    {
        return redirect()->intended(route('overview'))->with('flash_message', [
            'message' => $message,
            'type' => 'warning',
            'dismissible' => true,
        ]);
    }

    /**
     * Handle a failed two-factor verification attempt.
     */
    private function handleFailedVerification(int $userId): RedirectResponse
    {
        RateLimiter::hit($this->getRateLimitKey($userId));
        sleep(random_int(1, 3)); // Mitigate timing attacks

        return back()->withErrors(['code' => 'The provided two-factor code or recovery code was invalid.']);
    }

    /**
     * Generate a response for when the user has been rate limited.
     */
    private function rateLimitedResponse(int $userId): RedirectResponse
    {
        $seconds = RateLimiter::availableIn($this->getRateLimitKey($userId));

        return back()->withErrors(['code' => "Too many attempts. Please try again in {$seconds} seconds."]);
    }

    /**
     * Check if the provided code was a recovery code that was just used.
     */
    private function wasRecoveryCodeUsed(mixed $user, ?string $code): bool
    {
        if (! $code) {
            return false;
        }

        $recoveryCodes = $user->getRecoveryCodes();
        $usedCode = $recoveryCodes->firstWhere('code', $code);

        if (! $usedCode || ! isset($usedCode['used_at'])) {
            return false;
        }

        return Carbon::parse($usedCode['used_at'])->isAfter(now()->subSeconds(5));
    }
}
