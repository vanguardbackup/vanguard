<?php

declare(strict_types=1);

use App\Events\RemoteServerConnectivityStatusChanged;
use App\Livewire\RemoteServers\IndexItem;
use App\Models\RemoteServer;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('the component can be rendered', function (): void {

    $remoteServer = RemoteServer::factory()->create();

    $testable = Livewire::test(IndexItem::class, ['remoteServer' => $remoteServer]);

    $testable->assertOk();
});

test('the listener listens to event updates', function (): void {
    Toaster::fake();
    Event::fake([RemoteServerConnectivityStatusChanged::class]);

    $remoteServer = RemoteServer::factory()->create([
        'connectivity_status' => 'online',
    ]);

    $testable = Livewire::test(IndexItem::class, ['remoteServer' => $remoteServer]);

    RemoteServerConnectivityStatusChanged::dispatch($remoteServer, 'checking');

    Event::assertDispatched(RemoteServerConnectivityStatusChanged::class, fn ($event): bool => $event->remoteServer->is($remoteServer) && $event->connectivityStatus === 'checking');

    $testable->call('echoReceivedEvent');
    $testable->assertDispatched('$refresh');

    Toaster::assertDispatched(__('The connection to the remote server has been successfully established.'));
});

test('the listener updates the Livewire components', function (): void {
    $remoteServer = RemoteServer::factory()->create();

    $testable = Livewire::test(IndexItem::class, ['remoteServer' => $remoteServer]);

    $testable->call('updateLivewireComponents');
    $testable->assertDispatched('$refresh');
    $testable->assertDispatched('update-check-button-' . $remoteServer->id);
});
