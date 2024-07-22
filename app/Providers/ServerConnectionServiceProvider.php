<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServerConnection\ServerConnectionManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class ServerConnectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('server.connection', function ($app): ServerConnectionManager {
            return new ServerConnectionManager;
        });
    }

    public function boot(): void
    {
        $manager = $this->app->make('server.connection');
        $manager->defaultPrivateKey(Storage::disk('local')->path('app/ssh/id_rsa'));
        $manager->defaultPassphrase(config('app.ssh.passphrase'));

        if (! config('app.ssh.passphrase')) {
            throw new RuntimeException('SSH passphrase is not set in the configuration.');
        }
    }
}
