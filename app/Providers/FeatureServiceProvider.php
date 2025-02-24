<?php

declare(strict_types=1);

namespace App\Providers;

use Override;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

/**
 * Service provider for managing feature flags.
 *
 * This provider handles the definition and configuration of feature flags
 * used throughout the application. It allows for centralized management
 * of features that can be dynamically enabled or disabled.
 *
 * For more information on how to use and manage feature flags, please refer to:
 *
 * @see https://docs.vanguardbackup.com/experiments
 */
class FeatureServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * This method is called by Laravel during the service provider registration
     * process. It's used to bind things in the service container.
     */
    #[Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * This method is called after all other service providers have been registered.
     * It's used to perform any boot-time operations or register event listeners.
     */
    public function boot(): void
    {
        $this->defineFeatures();
    }

    /**
     * Define feature flags with additional metadata.
     *
     * This method sets up feature flags and their associated metadata.
     * It iterates through the defined features, setting up each feature
     * with Laravel Pennant and storing its metadata in the config.
     *
     * Features can be toggled via the Experiments manager in user settings.
     */
    private function defineFeatures(): void
    {
        $features = $this->getFeatures();

        foreach ($features as $key => $metadata) {
            Feature::define($key, fn (): bool => $this->isFeatureEnabled());
            config(["features.{$key}" => $metadata]);
        }
    }

    /**
     * Get the list of features with their metadata.
     *
     * This method returns an array of feature definitions. Each feature
     * is defined with a key and an array of metadata including title,
     * description, group, and icon.
     *
     * @return array<string, array{title: string, description: string, group: string, icon: string}>
     */
    private function getFeatures(): array
    {
        return [
            // 'example-feature' => [
            //     'title' => 'Example Feature',
            //     'description' => 'Description of the example feature.',
            //     'group' => 'General',
            //     'icon' => 'hugeicons-test-tube-01',
            // ],
        ];
    }

    /**
     * Determine if a feature is enabled.
     *
     * This method checks whether a specific feature is enabled.
     * The implementation of this method should include the logic
     * to determine the state of each feature.
     *
     * @return bool True if the feature is enabled, false otherwise
     */
    private function isFeatureEnabled(): bool
    {
        return false;
        // Default to disabled
    }
}
