<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to set the application language based on user preferences.
 * Falls back to a default language if the user is not authenticated or has an invalid language setting.
 */
class UserLanguage
{
    protected string $fallbackLanguage = 'en';

    /**
     * Handle an incoming request.
     *
     * Sets the application locale based on the authenticated user's language preference.
     * If no user is authenticated or the user's language is invalid, the fallback language is used.
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->fallbackLanguage);

        if (! Auth::check()) {
            return $next($request);
        }

        /** @var User $user */
        $user = Auth::user();

        $userLanguage = $user->getAttribute('language');

        if (array_key_exists($userLanguage, config('app.available_languages'))) {
            Carbon::setLocale($userLanguage);
            App::setLocale($userLanguage);
        }

        return $next($request);
    }
}
