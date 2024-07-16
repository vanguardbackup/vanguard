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
use Laravel\Socialite\Two\GithubProvider;
use Toaster;

class GitHubSocialiteController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     */
    public function redirectToProvider(): RedirectResponse|Redirect
    {
        if (! config('services.github.client_id') || ! config('services.github.client_secret')) {
            Log::debug('GitHub login is not enabled. Redirecting back to login.');

            return Redirect::route('login')->with('loginError', 'GitHub login is not enabled.');
        }

        /** @var GitHubProvider $githubProvider */
        $githubProvider = Socialite::driver('github');

        return $githubProvider
            ->scopes(['read:user'])
            ->redirect();
    }

    public function handleProviderCallback(): RedirectResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();

            if (($user = $this->findUserByGitHubId((int) $githubUser->getId())) instanceof User) {
                return $this->loginAndRedirect($user, 'Found GH ID associated with this user, logging them in.');
            }

            if (($user = $this->findUserByEmailAndUpdateGitHubId($githubUser)) instanceof User) {
                return $this->loginAndRedirect($user, "Adding the user's GH ID to their account.");
            }

            return $this->createUserAndLogin($githubUser);

        } catch (Exception $exception) {
            Log::error('GitHub OAuth login error: ' . $exception->getMessage());

            return Redirect::route('login')->with('error', 'Authentication failed. There may be an error with GitHub. Please try again later.');
        }
    }

    private function findUserByGitHubId(int $githubId): ?User
    {
        return User::where('github_id', $githubId)->first();
    }

    private function findUserByEmailAndUpdateGitHubId(SocialiteUser $socialiteUser): ?User
    {
        $user = User::where('email', $socialiteUser->getEmail())->first();

        $user?->update(['github_id' => $socialiteUser->getId()]);

        return $user;
    }

    private function createUserAndLogin(SocialiteUser $socialiteUser): RedirectResponse
    {
        $user = User::create([
            'name' => $socialiteUser->getName(),
            'email' => $socialiteUser->getEmail(),
            'github_id' => $socialiteUser->getId(),
        ]);

        Mail::to($user->email)->queue(new WelcomeMail($user));

        Log::debug('Creating new user with their GitHub ID and logging them in.', ['id' => $socialiteUser->getId()]);
        Auth::login($user);

        Toaster::success(__('Successfully logged in via GitHub!'));

        return Redirect::route('overview');
    }

    private function loginAndRedirect(User $user, string $message): RedirectResponse
    {
        Log::debug($message, ['id' => $user->getAttribute('github_id')]);
        Auth::login($user);

        return Redirect::route('overview');
    }
}
