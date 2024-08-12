<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\User\WelcomeMail;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Masmerise\Toaster\Toaster;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Handles GitLab OAuth authentication for the application.
 *
 * This controller manages the GitLab authentication flow, including
 * redirecting users to GitLab, handling callbacks, and user creation/login.
 */
class GitLabSocialiteController extends Controller
{
    /**
     * Redirect the user to the GitLab authentication page.
     */
    public function redirectToProvider(): Redirect|SymfonyRedirectResponse|RedirectResponse
    {
        if (! config('services.gitlab.client_id') || ! config('services.gitlab.client_secret')) {
            Log::debug('GitLab login is not enabled. Redirecting back to login.');

            return Redirect::route('login')->with('loginError', 'GitLab login is not enabled.');
        }

        return Socialite::driver('gitlab')->redirect();
    }

    /**
     * Handle the callback from GitLab after authentication.
     */
    public function handleProviderCallback(): RedirectResponse
    {
        try {
            $gitlabUser = Socialite::driver('gitlab')->user();
            $localUser = $this->findUserByEmail($gitlabUser->getEmail());

            if (! $localUser instanceof User) {
                $localUser = $this->createUser($gitlabUser);
            }

            Auth::login($localUser);

            Log::debug('Logging GitLab user in.', ['email' => $gitlabUser->getEmail()]);

            Toaster::success(__('Successfully logged in via GitLab!'));

            return Redirect::route('overview');
        } catch (Exception $exception) {
            Log::error('GitLab OAuth login error: ' . $exception->getMessage() . ' from ' . $exception::class);

            return Redirect::route('login')->with('loginError', 'Authentication failed. There may be an error with GitLab. Please try again later.');
        }
    }

    /**
     * Find a user by email and return the user's object if any.
     *
     * @param  null|string  $email  The email of the user
     */
    private function findUserByEmail(?string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Create a new user with GitLab data and return the user's object.
     */
    private function createUser(SocialiteUser $socialiteUser): User
    {
        $user = User::create([
            'name' => $socialiteUser->getName(),
            'email' => $socialiteUser->getEmail(),
        ]);

        Mail::to($user->email)->queue(new WelcomeMail($user));

        Log::debug('Creating new user from GitLab data.', ['email' => $socialiteUser->getEmail()]);

        return $user;
    }
}
