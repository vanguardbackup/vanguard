<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserLanguage
{
    protected string $fallbackLanguage = 'en';

    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->fallbackLanguage);

        if (! Auth::check()) {
            return $next($request);
        }

        $userLanguage = Auth::user()->language;

        if (array_key_exists($userLanguage, config('app.available_languages'))) {
            Carbon::setLocale($userLanguage);
            App::setLocale($userLanguage);
        }

        return $next($request);
    }
}
