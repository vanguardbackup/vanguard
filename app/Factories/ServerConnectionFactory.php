<?php

declare(strict_types=1);

namespace App\Factories;

use App\Contracts\ServerConnectionInterface;
use App\Enums\ConnectionType;
use App\Models\RemoteServer;
use App\Services\ServerConnection;
use App\Testing\ServerConnectionFake;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ServerConnectionFactory
{
    /**
     * Create a new ServerConnection instance.
     *
     * @param  int  $serverId  The ID of the RemoteServer
     * @param  ConnectionType  $connectionType  The type of connection (SSH or SFTP)
     * @param  int  $timeout  How long until the connection times out.
     *
     * @throws FileNotFoundException
     */
    public function make(int $serverId, ConnectionType $connectionType, int $timeout = 10): ServerConnectionInterface
    {
        if (app()->bound(ServerConnectionFake::class)) {
            $fake = app(ServerConnectionFake::class);
            $server = RemoteServer::findOrFail($serverId);

            return $fake->setConnectedServer($server);
        }

        $server = RemoteServer::findOrFail($serverId);

        return new ServerConnection($server, $connectionType);
    }

    /**
     * Create a new ServerConnection instance from a RemoteServer model.
     *
     * @param  RemoteServer  $remoteServer  The RemoteServer model
     * @param  ConnectionType  $connectionType  The type of connection (SSH or SFTP)
     * @param  int  $timeout  How long until the connection times out.
     *
     * @throws FileNotFoundException
     */
    public function makeFromModel(RemoteServer $remoteServer, ConnectionType $connectionType, int $timeout = 10): ServerConnectionInterface
    {
        if (app()->bound(ServerConnectionFake::class)) {
            return app(ServerConnectionFake::class)->setConnectedServer($remoteServer);
        }

        return new ServerConnection($remoteServer, $connectionType);
    }

    /**
     * Create a new SSH ServerConnection instance.
     *
     * @param  int  $serverId  The ID of the RemoteServer
     *
     * @throws ModelNotFoundException|FileNotFoundException If the RemoteServer is not found
     */
    public function makeSSH(int $serverId): ServerConnectionInterface
    {
        return $this->make($serverId, ConnectionType::SSH);
    }

    /**
     * Create a new SFTP ServerConnection instance.
     *
     * @param  int  $serverId  The ID of the RemoteServer
     *
     * @throws ModelNotFoundException|FileNotFoundException If the RemoteServer is not found
     */
    public function makeSFTP(int $serverId): ServerConnectionInterface
    {
        return $this->make($serverId, ConnectionType::SFTP);
    }
}
