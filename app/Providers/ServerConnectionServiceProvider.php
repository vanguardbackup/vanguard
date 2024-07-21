<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ServerConnectionInterface;
use App\Factories\ServerConnectionFactory;
use App\Services\ServerConnection;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Server Connection services.
 *
 * This provider is responsible for registering the ServerConnectionInterface
 * and ServerConnectionFactory in the Laravel service container. It ensures
 * that these services are available for dependency injection throughout the application.
 */
class ServerConnectionServiceProvider extends ServiceProvider
{
    /**
     * Register services related to server connections.
     *
     * This method binds the ServerConnectionInterface to the ServerConnectionFactory
     * and registers the ServerConnectionFactory in the service container.
     */
    public function register(): void
    {
        $this->app->bind(ServerConnectionInterface::class, ServerConnection::class);
    }

    /**
     * Bootstrap any application services related to server connections.
     *
     * This method is called after all other service providers have been registered.
     * It can be used to perform any actions that are necessary when the application is starting up.
     */
    public function boot(): void
    {
        // Currently, no bootstrapping is required for server connection services.
    }
}
