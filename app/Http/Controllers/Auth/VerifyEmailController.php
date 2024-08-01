<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Handles email verification for users.
 *
 * This controller is responsible for verifying user emails
 * and redirecting them to the appropriate page after verification.
 */
class VerifyEmailController extends Controller
{
    /**
     * Handle the email verification process.
     *
     * This method verifies the user's email if not already verified,
     * triggers a Verified event, and redirects to the overview page.
     */
    public function __invoke(EmailVerificationRequest $emailVerificationRequest): RedirectResponse
    {
        /** @var MustVerifyEmail $user */
        $user = $emailVerificationRequest->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('overview', absolute: false) . '?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(route('overview', absolute: false) . '?verified=1');
    }
}
