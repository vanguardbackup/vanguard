<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to handle requests for users with disabled accounts.
 *
 * This middleware checks if the authenticated user's account is disabled.
 * If so, it logs them out, clears their session, and redirects to the login page.
 */
class CheckAccountState
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The incoming request.
     * @param  Closure  $next  The next middleware/handler in the chain.
     * @return Response The response to send back to the user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()?->hasSuspendedAccount()) {
            // Clear the user's session
            Auth::logout();
            Session::flush();

            // Regenerate the session ID for security
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('loginError', 'Your account has been suspended.');
        }

        return $next($request);
    }
}
