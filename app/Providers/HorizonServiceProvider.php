<?php

declare(strict_types=1);

namespace App\Providers;

use Override;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

/**
 * Service provider for Laravel Horizon configuration.
 * Handles Horizon access control and notification routing.
 */
class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services for Horizon.
     *
     * Configures notification routes for Horizon (currently commented out).
     */
    #[Override]
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Define the Horizon access gate.
     *
     * Allows access via a bearer token or for authenticated admin users.
     */
    #[Override]
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (): bool {

            if (request()->bearerToken() && request()->bearerToken() === config('services.horizon.token')) {
                return true;
            }

            return Auth::check() && Auth::user()?->isAdmin();
        });
    }
}
