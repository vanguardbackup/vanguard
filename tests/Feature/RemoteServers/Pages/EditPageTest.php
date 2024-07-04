<?php

declare(strict_types=1);

use App\Models\RemoteServer;
use App\Models\User;

test('the page can be rendered by by the owner of the remote server', function () {

    $user = User::factory()->create();

    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('remote-servers.edit', $remoteServer));

    $response->assertOk();
    $response->assertViewIs('remote-servers.edit');
    $response->assertViewHas('remoteServer', $remoteServer);

    $this->assertAuthenticatedAs($user);
    $this->assertEquals($user->id, $remoteServer->user_id);
    $this->assertFalse($remoteServer->isMarkedForDeletion());
});

test('the page cannot be rendered if the remote server has been marked for deletion', function () {

    $user = User::factory()->create();

    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
    ]);

    $remoteServer->setMarkedForDeletion();

    $response = $this->actingAs($user)->get(route('remote-servers.edit', $remoteServer));

    $response->assertNotFound();

    $this->assertAuthenticatedAs($user);
    $this->assertEquals($user->id, $remoteServer->user_id);
    $this->assertTrue($remoteServer->isMarkedForDeletion());
});

test('the page is not rendered by unauthorized users', function () {

    $user = User::factory()->create();

    $remoteServer = RemoteServer::factory()->create();

    $response = $this->actingAs($user)->get(route('remote-servers.edit', $remoteServer));

    $response->assertForbidden();

    $this->assertAuthenticatedAs($user);

    $this->assertNotEquals($user->id, $remoteServer->user_id);
});

test('the page is not rendered by guests', function () {

    $remoteServer = RemoteServer::factory()->create();

    $response = $this->get(route('remote-servers.edit', $remoteServer));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});
