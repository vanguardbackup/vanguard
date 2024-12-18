<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use App\Models\UserConnection;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;
use Toaster;

/**
 * Manages the user's external service connections page in the profile section.
 * Displays and allows management of connected services like GitHub, GitLab, etc.
 */
class ConnectionsPage extends Component
{
    /**
     * Array of active connection provider names.
     *
     * @var array<int, string>
     */
    public array $activeConnections = [];

    /**
     * Initialize the component state.
     */
    public function mount(): void
    {
        $this->loadActiveConnections();
    }

    /**
     * Initiates the connection process for a given provider.
     */
    public function connect(string $provider): void
    {
        $route = match ($provider) {
            'github' => 'github.redirect',
            'gitlab' => 'gitlab.redirect',
            'bitbucket' => 'bitbucket.redirect',
            default => null,
        };

        if ($route === null) {
            Toaster::error("Unsupported provider: {$provider}");

            return;
        }

        $this->redirect(route($route));
    }

    /**
     * Contacts the relevant provider to retrieve basic user account details, such as username, profile link, and avatar URL.
     * The data is cached to avoid repetitive API calls when navigating to the connections route.
     *
     * @return array{username: string|null, link: string|null, avatar_url: string|null} Normalized user details.
     */
    public function contactProvider(string $provider): array
    {
        $baseApiUrl = match ($provider) {
            'github' => 'https://api.github.com/user',
            'gitlab' => 'https://gitlab.com/api/v4/user',
            'bitbucket' => 'https://api.bitbucket.org/2.0/user',
            default => throw new RuntimeException("Unsupported provider: {$provider}"),
        };

        /** @var User $user */
        $user = Auth::user();
        $connection = $user->connections()->where('provider_name', $provider)->first();

        if (! $connection || ! $connection->getAttribute('access_token')) {
            throw new RuntimeException("No valid connection found for {$provider}.");
        }

        // Generate a cache key
        $cacheKey = "provider_data:{$provider}:user:{$user->getAttribute('id')}";

        return cache()->remember($cacheKey, now()->addMinutes(15), function () use ($baseApiUrl, $provider, $connection): array {
            try {
                $response = Http::withToken($connection->getAttribute('access_token'))->get($baseApiUrl);

                if ($response->failed()) {
                    throw new RuntimeException("Failed to contact {$provider} API.");
                }

                $data = $response->json();

                // Normalize response
                return match ($provider) {
                    'github' => [
                        'username' => $data['login'] ?? null,
                        'link' => $data['html_url'] ?? null,
                        'avatar_url' => $data['avatar_url'] ?? null,
                    ],
                    'gitlab' => [
                        'username' => $data['username'] ?? null,
                        'link' => $data['web_url'] ?? null,
                        'avatar_url' => $data['avatar_url'] ?? null,
                    ],
                    'bitbucket' => [
                        'username' => $data['username'] ?? ($data['display_name'] ?? null),
                        'link' => $data['links']['html']['href'] ?? null,
                        'avatar_url' => $data['links']['avatar']['href'] ?? null,
                    ],
                    default => ['username' => null, 'link' => null, 'avatar_url' => null],
                };
            } catch (Exception $e) {
                report($e);
                Toaster::error("Error retrieving data from {$provider}.");

                // Return the structure with null values in case of an error
                return ['username' => null, 'link' => null, 'avatar_url' => null];
            }
        });
    }

    /**
     * Disconnects a service for the current user.
     */
    public function disconnect(string $provider): void
    {
        /** @var User $user */
        $user = Auth::user();
        $deleted = $user->connections()->where('provider_name', $provider)->delete();

        if ($deleted) {
            // Clear cached data for the disconnected provider
            $cacheKey = "provider_data:{$provider}:user:{$user->getAttribute('id')}";
            cache()->forget($cacheKey);

            $this->loadActiveConnections();
            Toaster::success(ucfirst($provider) . ' account unlinked successfully!');
        } else {
            Toaster::error("No active connection found for {$provider}.");
        }
    }

    /**
     * Refreshes the token for a given provider.
     */
    public function refresh(string $provider): void
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var UserConnection|null $connection */
        $connection = $user->connections()->where('provider_name', $provider)->first();

        if (! $connection || ! $connection->getAttribute('refresh_token')) {
            Toaster::error('Unable to refresh token. Please re-link your account.');

            return;
        }

        try {
            $providerInstance = Socialite::driver($provider);

            if (! ($providerInstance instanceof AbstractProvider)) {
                throw new RuntimeException('Provider does not support token refresh.');
            }

            $newToken = $providerInstance->refreshToken($connection->getAttribute('refresh_token'));
            $connection->setAttribute('access_token', $newToken->token);
            $connection->setAttribute('refresh_token', $newToken->refreshToken);
            $connection->setAttribute('token_expires_at', $newToken->expiresIn ? now()->addSeconds($newToken->expiresIn)->toDateTimeString() : null);
            $connection->save();

            // Clear cached data for the refreshed provider
            $cacheKey = "provider_data:{$provider}:user:{$user->getAttribute('id')}";
            cache()->forget($cacheKey);

            Toaster::success(ucfirst($provider) . ' token refreshed successfully!');
        } catch (Exception $e) {
            report($e);
            Toaster::error('Failed to refresh token. Please try re-linking your account.');
        }
    }

    /**
     * Checks if a given provider is connected for the current user.
     */
    #[Computed]
    public function isConnected(string $provider): bool
    {
        return in_array($provider, $this->activeConnections, true);
    }

    /**
     * Checks if a refresh token exists for a given provider.
     */
    #[Computed]
    public function hasRefreshToken(string $provider): bool
    {
        /** @var User $user */
        $user = Auth::user();
        $connection = $user->connections()->where('provider_name', $provider)->first();

        return $connection && ! empty($connection->getAttribute('refresh_token'));
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.profile.connections-page')
            ->layout('components.layouts.account-app');
    }

    /**
     * Load the user's active connections.
     */
    private function loadActiveConnections(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->activeConnections = $user->connections()->pluck('provider_name')->toArray();
    }
}
