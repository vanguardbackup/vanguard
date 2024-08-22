<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use App\Models\UserConnection;
use Exception;
use Illuminate\Support\Facades\Auth;
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
            default => null,
        };

        if ($route === null) {
            Toaster::error("Unsupported provider: {$provider}");

            return;
        }

        $this->redirect(route($route));
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

            Toaster::success(ucfirst($provider) . ' token refreshed successfully!');
        } catch (Exception) {
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
