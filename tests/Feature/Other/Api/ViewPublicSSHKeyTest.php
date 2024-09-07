<?php

declare(strict_types=1);

use App\Facades\ServerConnection;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('can view ssh key if the user has the create remote server ability', function (): void {
    test_create_keys();

    ServerConnection::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['create-remote-servers']);

    $response = $this->getJson('/api/ssh-key');

    $response->assertStatus(200)
        ->assertJsonStructure(['public_key']);
});

test('cannot view ssh key if the user lacks permission', function (): void {
    ServerConnection::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['view-backup-destinations']); // purposeful wrong permission

    $response = $this->getJson('/api/ssh-key');

    $response->assertForbidden();
});

test('cannot view the ssh key if it does not exist', function (): void {
    $sshKeyPath = storage_path('app/ssh');
    $privateKeyFile = $sshKeyPath . '/key';
    $publicKeyFile = $sshKeyPath . '/key.pub';

    if (! file_exists($sshKeyPath)) {
        mkdir($sshKeyPath, 0755, true);
    }

    if (file_exists($privateKeyFile)) {
        @unlink($privateKeyFile);
    }
    if (file_exists($publicKeyFile)) {
        @unlink($publicKeyFile);
    }

    $user = User::factory()->create();
    Sanctum::actingAs($user, ['create-remote-servers']);

    $response = $this->getJson('/api/ssh-key');

    $response->assertStatus(404)
        ->assertJson(['message' => 'Please generate SSH keys first.']);

    @rmdir($sshKeyPath);
});
