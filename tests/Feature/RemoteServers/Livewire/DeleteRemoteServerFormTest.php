<?php

use App\Livewire\RemoteServers\DeleteRemoteServerForm;
use App\Models\RemoteServer;
use App\Models\User;

test('the component can be rendered', function () {

    Livewire::test(DeleteRemoteServerForm::class, ['remoteServer' => RemoteServer::factory()->create()])
        ->assertStatus(200);
});

test('a remote server can be deleted by its creator', function () {

    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(DeleteRemoteServerForm::class, ['remoteServer' => $remoteServer])
        ->call('delete');

    $this->assertDatabaseMissing('remote_servers', ['id' => $remoteServer->id]);
    $this->assertAuthenticatedAs($user);
});

test('a remote server cannot be deleted by another user', function () {

    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create();

    $this->actingAs($user);

    Livewire::test(DeleteRemoteServerForm::class, ['remoteServer' => $remoteServer])
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('remote_servers', ['id' => $remoteServer->id]);
    $this->assertAuthenticatedAs($user);
});
