<?php

declare(strict_types=1);

namespace App\Http\Controllers\Connections;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Toaster;

abstract class ConnectionsController extends Controller
{
    /**
     * Redirect the user to the provider's authentication page.
     */
    protected function redirectToProvider(string $provider): SymfonyRedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the provider callback for authentication or account linking.
     */
    protected function handleProviderCallback(string $provider): RedirectResponse
    {
        $socialiteUser = Socialite::driver($provider)->user();

        $existingConnection = UserConnection::where('provider_name', $provider)
            ->where('provider_user_id', $socialiteUser->getId())
            ->first();

        if ($existingConnection) {
            return $this->signInWithExistingConnection($existingConnection);
        }

        $user = Auth::user();
        if ($user instanceof User) {
            return $this->linkAccount($user, $provider, $socialiteUser);
        }

        return $this->registerOrSignInUser($provider, $socialiteUser);
    }

    /**
     * Sign in with an existing connection.
     */
    protected function signInWithExistingConnection(UserConnection $userConnection): RedirectResponse
    {
        $user = $userConnection->getAttribute('user');
        if ($user instanceof User) {
            Auth::login($user);

            return redirect()->route('overview');
        }

        Toaster::error('Unable to sign in. User not found.');

        return redirect()->route('login');
    }

    /**
     * Register a new user or sign in an existing user based on their email.
     */
    protected function registerOrSignInUser(string $provider, SocialiteUser $socialiteUser): RedirectResponse
    {
        $user = User::where('email', $socialiteUser->getEmail())->first();

        if ($user instanceof User) {
            $this->createConnection($user, $provider, $socialiteUser);
            Auth::login($user);
            Toaster::success(ucfirst($provider) . ' account successfully linked and authenticated.');

            return redirect()->route('overview');
        }

        $user = User::create([
            'name' => $socialiteUser->getName(),
            'email' => $socialiteUser->getEmail(),
        ]);

        $this->createConnection($user, $provider, $socialiteUser);
        Auth::login($user);

        Toaster::success('Account created and authenticated via ' . ucfirst($provider) . '.');

        return redirect()->route('overview');
    }

    /**
     * Link a provider account to an existing user.
     */
    protected function linkAccount(User $user, string $provider, SocialiteUser $socialiteUser): RedirectResponse
    {
        $this->createConnection($user, $provider, $socialiteUser);

        Toaster::success(ucfirst($provider) . ' account successfully linked to your profile.');

        return redirect()->route('profile.connections');
    }

    /**
     * Create a new user connection.
     */
    protected function createConnection(User $user, string $provider, SocialiteUser $socialiteUser): void
    {
        $expiresIn = method_exists($socialiteUser, 'getExpiresIn') ? $socialiteUser->getExpiresIn() : null;
        $approvedScopes = method_exists($socialiteUser, 'getApprovedScopes') ? $socialiteUser->getApprovedScopes() : null;

        // Handle access token retrieval
        $accessToken = null;
        if (method_exists($socialiteUser, 'getToken')) {
            $accessToken = $socialiteUser->getToken();
        } elseif (property_exists($socialiteUser, 'token')) {
            $accessToken = $socialiteUser->token;
        } elseif (method_exists($socialiteUser, 'accessTokenResponseBody')) {
            $tokenResponse = $socialiteUser->accessTokenResponseBody();
            $accessToken = $tokenResponse['access_token'] ?? null;
        }

        // Handle refresh token retrieval
        $refreshToken = null;
        if (method_exists($socialiteUser, 'getRefreshToken')) {
            $refreshToken = $socialiteUser->getRefreshToken();
        } elseif (property_exists($socialiteUser, 'refreshToken')) {
            $refreshToken = $socialiteUser->refreshToken;
        }

        $user->connections()->create([
            'provider_name' => $provider,
            'provider_user_id' => $socialiteUser->getId(),
            'provider_email' => $socialiteUser->getEmail(),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            'scopes' => $approvedScopes,
        ]);
    }
}
