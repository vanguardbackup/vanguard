<?php

declare(strict_types=1);

use App\Livewire\RemoteServers\CheckConnectionButton;
use App\Models\RemoteServer;

it('can be rendered', function () {

    $remoteServer = RemoteServer::factory()->create();
    $component = Livewire::test(CheckConnectionButton::class, ['remoteServer' => $remoteServer]);

    $component->assertOk();
});

it('can check connection', function () {
    Event::fake();
    Toaster::fake();

    $remoteServer = RemoteServer::factory()->create();
    $component = Livewire::test(CheckConnectionButton::class, ['remoteServer' => $remoteServer]);

    $component->call('checkConnection');

    $component->assertDispatched('connection-check-initiated-' . $remoteServer->id);

    Toaster::assertDispatched(__('Performing a connectivity check.'));
});

it('can refresh self', function () {

    $remoteServer = RemoteServer::factory()->create();
    $component = Livewire::test(CheckConnectionButton::class, ['remoteServer' => $remoteServer]);

    $component->call('refreshSelf');

    $component->assertDispatched('$refresh');
});
