<?php

use App\Events\RemoteServerConnectivityStatusChanged;
use App\Livewire\RemoteServers\IndexItem;
use App\Models\RemoteServer;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('the component can be rendered', function () {

    $remoteServer = RemoteServer::factory()->create();

    $component = Livewire::test(IndexItem::class, ['remoteServer' => $remoteServer]);

    $component->assertOk();
});

test('the listener listens to event updates', function () {
    Toaster::fake();
    Event::fake([RemoteServerConnectivityStatusChanged::class]);

    $remoteServer = RemoteServer::factory()->create([
        'connectivity_status' => 'online',
    ]);

    $component = Livewire::test(IndexItem::class, ['remoteServer' => $remoteServer]);

    RemoteServerConnectivityStatusChanged::dispatch($remoteServer, 'checking');

    Event::assertDispatched(RemoteServerConnectivityStatusChanged::class, function ($event) use ($remoteServer) {
        return $event->remoteServer->is($remoteServer) && $event->connectivityStatus === 'checking';
    });

    $component->call('echoReceivedEvent');
    $component->assertDispatched('$refresh');

    Toaster::assertDispatched(__('The connection to the remote server has been successfully established.'));
});

test('the listener updates the Livewire components', function () {
    $remoteServer = RemoteServer::factory()->create();

    $component = Livewire::test(IndexItem::class, ['remoteServer' => $remoteServer]);

    $component->call('updateLivewireComponents');
    $component->assertDispatched('$refresh');
    $component->assertDispatched('update-check-button-' . $remoteServer->id);
});
