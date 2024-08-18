<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\GreetingService;
use Feature;
use Flare;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

/**
 * Core application service provider.
 * Handles service registration and authorization setup.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        $this->registerFlareVersion();
        $this->registerGreetingService();
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->defineGates();

        /*        Feature::define('example-experiment', function () {
                    return false;
                });*/
    }

    /**
     * Set up version determination for Flare.
     */
    private function registerFlareVersion(): void
    {
        Flare::determineVersionUsing(function () {
            $versionFile = base_path('VERSION');

            if (! File::exists($versionFile)) {
                return __('Unknown');
            }

            return str_replace("\n", '', File::get($versionFile));
        });
    }

    /**
     * Register the GreetingService as a singleton.
     */
    private function registerGreetingService(): void
    {
        $this->app->singleton(GreetingService::class);
        $this->app->alias(GreetingService::class, 'Greeting');
    }

    /**
     * Define application authorization gates.
     */
    private function defineGates(): void
    {
        Gate::define('viewPulse', fn (User $user): bool => $user->isAdmin());
    }
}
