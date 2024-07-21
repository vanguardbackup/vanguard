<?php

declare(strict_types=1);

use App\Actions\RemoteServer\CheckRemoteServerConnection;
use App\Events\RemoteServerConnectivityStatusChanged;
use App\Facades\ServerConnection;
use App\Factories\ServerConnectionFactory;
use App\Models\RemoteServer;
use Illuminate\Support\Facades\Event;

it('can check that a server is online', function (): void {
    Event::fake();
    $serverConnectionFake = ServerConnection::fake();
    $serverConnectionFake->shouldConnect();

    $remoteServer = RemoteServer::factory()->create([
        'connectivity_status' => RemoteServer::STATUS_OFFLINE,
    ]);

    $action = new CheckRemoteServerConnection(new ServerConnectionFactory);
    $result = $action->byRemoteServerId($remoteServer->id);

    expect($result)->toBe([
        'connectivity_status' => RemoteServer::STATUS_ONLINE,
    ]);

    $remoteServer->refresh();

    expect($remoteServer->isOnline())->toBeTrue();
    ServerConnection::assertConnected();
    Event::assertDispatched(RemoteServerConnectivityStatusChanged::class);
});

it('can check that a server is offline', function (): void {
    ServerConnection::fake();
    Event::fake();

    $remoteServer = RemoteServer::factory()->create([
        'connectivity_status' => RemoteServer::STATUS_ONLINE,
    ]);

    $action = new CheckRemoteServerConnection(new ServerConnectionFactory);
    $action->byRemoteServerId($remoteServer->id);

    $remoteServer->refresh();

    expect($remoteServer->isOffline())->toBeTrue();
    Event::assertDispatched(RemoteServerConnectivityStatusChanged::class);
    ServerConnection::assertNotConnected();
});

it('uses the current connectivity status if not provided', function (): void {
    $remoteServer = RemoteServer::factory()->create([
        'connectivity_status' => RemoteServer::STATUS_ONLINE,
    ]);

    $event = new RemoteServerConnectivityStatusChanged($remoteServer);

    expect($event->connectivityStatus)->toBe(RemoteServer::STATUS_ONLINE);
});
