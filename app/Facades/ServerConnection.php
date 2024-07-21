<?php

declare(strict_types=1);

namespace App\Facades;

use App\Testing\ServerConnectionFake;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for ServerConnection operations.
 *
 * This facade provides a static interface to server connection operations
 * and testing utilities.
 *
 * @method static void assertConnected()
 * @method static void assertNotConnected()
 * @method static void assertConnectedTo(callable $callback)
 * @method static void assertCommandExecuted(string $command)
 * @method static void assertFileUploaded(string $localPath, string $remotePath)
 * @method static void assertFileDownloaded(string $remotePath, string $localPath)
 *
 * @see ServerConnectionFake
 * @see ServerConnection
 */
class ServerConnection extends Facade
{
    /**
     * Replace the bound instance with a fake for testing.
     */
    public static function fake(): ServerConnectionFake
    {
        static::swap($serverConnectionFake = new ServerConnectionFake);

        return $serverConnectionFake;
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'server.connection';
    }
}
