<?php

declare(strict_types=1);

namespace App\Facades;

use Override;
use App\Models\RemoteServer;
use App\Support\ServerConnection\Fakes\ServerConnectionFake;
use App\Support\ServerConnection\PendingConnection;
use App\Support\ServerConnection\ServerConnectionManager;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for ServerConnectionManager.
 *
 * This facade provides a static interface to the ServerConnectionManager,
 * allowing for easy access to server connection functionality throughout the application.
 *
 * @method static PendingConnection connect(string $host = '', int $port = 22, string $username = 'root')
 * @method static PendingConnection connectFromModel(RemoteServer $server)
 * @method static ServerConnectionFake fake()
 * @method static void assertConnected()
 * @method static void assertDisconnected()
 * @method static void assertNotConnected()
 * @method static void assertCommandRan(?string $command = null)
 * @method static void assertAnyCommandRan()
 * @method static void assertNoCommandsRan()
 * @method static void assertFileUploaded(string $localPath, string $remotePath)
 * @method static void assertFileDownloaded(string $remotePath, string $localPath)
 * @method static void assertOutput(string $output)
 * @method static void assertConnectionAttempted(array $connectionDetails)
 * @method static void defaultPrivateKey(string $path)
 * @method static void defaultPassphrase(string $passphrase)
 * @method static ServerConnectionFake shouldConnect()
 * @method static ServerConnectionFake shouldNotConnect()
 * @method static string getPrivateKeyContent(string $path)
 * @method static string getDefaultPrivateKey()
 * @method static string getDefaultPassphrase()
 * @method static string getDefaultPrivateKeyPath()
 * @method static string getDefaultPublicKeyPath()
 * @method static string getDefaultPublicKey()
 * @method static string getPublicKeyContent(string $path)
 *
 * @see ServerConnectionManager
 */
class ServerConnection extends Facade
{
    /**
     * Get the registered name of the component.
     */
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return ServerConnectionManager::class;
    }
}
