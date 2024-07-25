<?php

declare(strict_types=1);

use App\Actions\RemoteServer\CheckRemoteServerConnection;
use App\Events\RemoteServerConnectivityStatusChanged;
use App\Facades\ServerConnection;
use App\Models\RemoteServer;
use Illuminate\Support\Facades\Event;

it('can check that a server is online', function (): void {
    Event::fake();

    $fake = ServerConnection::fake();
    $fake->shouldConnect();

    $remoteServer = RemoteServer::factory()->create([
        'connectivity_status' => RemoteServer::STATUS_OFFLINE,
    ]);

    $action = new CheckRemoteServerConnection;
    $result = $action->byRemoteServerId($remoteServer->id);

    expect($result)->toBe([
        'status' => 'success',
        'connectivity_status' => RemoteServer::STATUS_ONLINE,
        'message' => 'Successfully connected to remote server',
    ]);

    $fake->assertConnectionAttempted([
        'host' => $remoteServer->ip_address,
        'port' => $remoteServer->port,
        'username' => $remoteServer->username,
    ]);

    $fake->assertConnected();

    $remoteServer->refresh();

    expect($remoteServer->isOnline())->toBeTrue();
    Event::assertDispatched(RemoteServerConnectivityStatusChanged::class);
});

it('can check that a server is offline', function (): void {
    Event::fake();

    ServerConnection::fake()->shouldNotConnect();

    $remoteServer = RemoteServer::factory()->create([
        'connectivity_status' => RemoteServer::STATUS_ONLINE,
    ]);

    $action = new CheckRemoteServerConnection;
    $result = $action->byRemoteServerId($remoteServer->id);

    expect($result)->toBe([
        'status' => 'error',
        'connectivity_status' => RemoteServer::STATUS_OFFLINE,
        'message' => 'Server is offline or unreachable',
        'error' => 'Failed to establish a connection with the remote server.',
    ]);

    ServerConnection::assertConnectionAttempted([
        'host' => $remoteServer->ip_address,
        'port' => $remoteServer->port,
        'username' => $remoteServer->username,
    ]);

    ServerConnection::assertNotConnected();

    $remoteServer->refresh();

    expect($remoteServer->isOffline())->toBeTrue();
    Event::assertDispatched(RemoteServerConnectivityStatusChanged::class);
});

it('uses the current connectivity status if not provided', function (): void {
    $remoteServer = RemoteServer::factory()->create([
        'connectivity_status' => RemoteServer::STATUS_ONLINE,
    ]);

    $event = new RemoteServerConnectivityStatusChanged($remoteServer);

    expect($event->connectivityStatus)->toBe(RemoteServer::STATUS_ONLINE);
});
