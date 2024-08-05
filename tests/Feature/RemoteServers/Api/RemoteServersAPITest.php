<?php

declare(strict_types=1);

use App\Models\RemoteServer;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('user can list their remote servers', function (): void {
    Sanctum::actingAs($this->user, ['manage-remote-servers']);

    RemoteServer::factory()->count(3)->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/remote-servers');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'user_id', 'label', 'ip_address', 'username', 'port', 'connectivity_status', 'last_connected_at', 'is_password_set', 'created_at', 'updated_at'],
            ],
            'links',
            'meta',
        ]);
});

test('user cannot list remote servers without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $response = $this->getJson('/api/remote-servers');

    $response->assertStatus(403);
});

test('user can create a new remote server', function (): void {
    Sanctum::actingAs($this->user, ['manage-remote-servers']);

    $serverData = [
        'label' => 'Test Server',
        'ip_address' => '192.168.1.1',
        'username' => 'testuser',
        'port' => 22,
        'database_password' => 'secret',
    ];

    $response = $this->postJson('/api/remote-servers', $serverData);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'label' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'username' => 'testuser',
            'port' => 22,
            'is_password_set' => true,
        ]);

    $this->assertDatabaseHas('remote_servers', [
        'label' => 'Test Server',
        'ip_address' => '192.168.1.1',
        'username' => 'testuser',
        'port' => 22,
    ]);

    $server = RemoteServer::where('ip_address', '192.168.1.1')->first();
    $this->assertTrue(Crypt::decryptString($server->database_password) === 'secret');
});

test('user cannot create a remote server without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $response = $this->postJson('/api/remote-servers', [
        'label' => 'Test Server',
        'ip_address' => '192.168.1.1',
        'username' => 'testuser',
        'port' => 22,
    ]);

    $response->assertStatus(403);
});

test('user can view a specific remote server', function (): void {
    Sanctum::actingAs($this->user, ['manage-remote-servers']);

    $server = RemoteServer::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/remote-servers/{$server->id}");

    $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $server->id,
            'label' => $server->label,
            'ip_address' => $server->ip_address,
        ]);
});

test('user cannot view a remote server without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $server = RemoteServer::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/remote-servers/{$server->id}");

    $response->assertStatus(403);
});

test('user can update their remote server', function (): void {
    Sanctum::actingAs($this->user, ['manage-remote-servers']);

    $server = RemoteServer::factory()->create(['user_id' => $this->user->id]);

    $updatedData = [
        'label' => 'Updated Server',
        'ip_address' => '192.168.1.2',
        'username' => 'updateduser',
        'port' => 2222,
        'database_password' => 'newsecret',
    ];

    $response = $this->putJson("/api/remote-servers/{$server->id}", $updatedData);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'label' => 'Updated Server',
            'ip_address' => '192.168.1.2',
            'username' => 'updateduser',
            'port' => 2222,
            'is_password_set' => true,
        ]);

    $updatedServer = $server->fresh();
    $this->assertTrue(Crypt::decryptString($updatedServer->database_password) === 'newsecret');
});

test('user can update their remote server without changing password', function (): void {
    Sanctum::actingAs($this->user, ['manage-remote-servers']);

    $server = RemoteServer::factory()->create([
        'user_id' => $this->user->id,
        'database_password' => Crypt::encryptString('oldsecret'),
    ]);

    $updatedData = [
        'label' => 'Updated Server',
        'ip_address' => '192.168.1.2',
        'username' => 'updateduser',
        'port' => 2222,
    ];

    $response = $this->putJson("/api/remote-servers/{$server->id}", $updatedData);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'label' => 'Updated Server',
            'ip_address' => '192.168.1.2',
            'username' => 'updateduser',
            'port' => 2222,
            'is_password_set' => true,
        ]);

    $updatedServer = $server->fresh();
    $this->assertTrue(Crypt::decryptString($updatedServer->database_password) === 'oldsecret');
});

test('user cannot update a remote server without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $server = RemoteServer::factory()->create(['user_id' => $this->user->id]);

    $response = $this->putJson("/api/remote-servers/{$server->id}", [
        'label' => 'Updated Server',
    ]);

    $response->assertStatus(403);
});

test('user can delete their remote server', function (): void {
    Sanctum::actingAs($this->user, ['manage-remote-servers']);

    $server = RemoteServer::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/remote-servers/{$server->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('remote_servers', ['id' => $server->id]);
});

test('user cannot delete a remote server without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $server = RemoteServer::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/remote-servers/{$server->id}");

    $response->assertStatus(403);
});
