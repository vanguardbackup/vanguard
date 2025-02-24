<?php

declare(strict_types=1);

namespace App\Providers;

use Override;
use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

/**
 * Service provider for Livewire Volt configuration.
 * Handles the setup and mounting of Volt components.
 */
class VoltServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Currently, not in use, but available for future service registrations.
     */
    #[Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap Volt services.
     *
     * Mounts Volt components from specified view directories.
     */
    public function boot(): void
    {
        Volt::mount([
            config('livewire.view_path', resource_path('views/livewire')),
            resource_path('views/pages'),
        ]);
    }
}
