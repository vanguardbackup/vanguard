<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServerConnection\ServerConnectionManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

/**
 * Service Provider for Server Connection.
 *
 * This provider is responsible for setting up the ServerConnectionManager
 * and configuring its default values for private key and passphrase.
 */
class ServerConnectionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * This method binds the ServerConnectionManager to the service container
     * as a singleton instance.
     */
    public function register(): void
    {
        $this->app->singleton('server.connection', function (): ServerConnectionManager {
            return new ServerConnectionManager;
        });
    }

    /**
     * Bootstrap services.
     *
     * This method sets up the default private key and passphrase for
     * the ServerConnectionManager. The default values are fine and
     * this shouldn't have to be altered for any purposes.
     */
    public function boot(): void
    {
        ServerConnectionManager::defaultPrivateKey(Storage::disk('local')->path('app/ssh/id_rsa'));
        ServerConnectionManager::defaultPassphrase(config('app.ssh.passphrase'));
    }
}
