<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\GreetingService;
use Feature;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

/**
 * Core application service provider.
 * Handles service registration, authorization setup, and feature flags.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        $this->registerGreetingService();
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->defineGates();
        $this->defineFeatures();
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

    /**
     * Define feature flags with additional metadata.
     */
    private function defineFeatures(): void
    {
        $features = [
            'navigation-redesign' => [
                'title' => 'Navigation Redesign',
                'description' => 'A new, more intuitive navigation structure for improved user experience.',
                'group' => 'UI/UX',
                'icon' => 'heroicon-o-bars-3',
            ],
        ];

        foreach ($features as $key => $metadata) {
            Feature::define($key, function (): false {
                return false;
            });

            // Store the metadata for later use
            config(["features.{$key}" => $metadata]);
        }
    }
}
