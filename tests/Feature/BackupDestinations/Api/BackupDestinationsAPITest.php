<?php

declare(strict_types=1);

use App\Models\BackupDestination;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('user with view permission can list backup destinations', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-destinations']);

    BackupDestination::factory()->count(3)->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson('/api/backup-destinations');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'label', 'type', 'user_id', 'created_at', 'updated_at'],
            ],
        ])
        ->assertJsonCount(3, 'data')
        ->assertJsonMissing(['s3_access_key', 's3_secret_key']);
});

test('user without view permission cannot list backup destinations', function (): void {
    Sanctum::actingAs($this->user, ['create-backup-destinations']);

    $response = $this->getJson('/api/backup-destinations');

    $response->assertStatus(403);
});

test('user with create permission can create a backup destination', function (): void {
    Sanctum::actingAs($this->user, ['create-backup-destinations']);

    $backupDestinationData = [
        'label' => 'Test Backup',
        'type' => 's3',
        's3_access_key' => 'test_access_key',
        's3_secret_key' => 'test_secret_key',
        's3_bucket_name' => 'test-bucket',
        'custom_s3_region' => 'us-west-2',
        'path_style_endpoint' => false,
    ];

    $response = $this->postJson('/api/backup-destinations', $backupDestinationData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'label', 'type', 'user_id', 'created_at', 'updated_at'],
        ])
        ->assertJsonFragment([
            'label' => 'Test Backup',
            'type' => 's3',
            's3_bucket_name' => 'test-bucket',
            'custom_s3_region' => 'us-west-2',
            'path_style_endpoint' => false,
        ])
        ->assertJsonMissing(['s3_access_key', 's3_secret_key']);

    $this->assertDatabaseHas('backup_destinations', [
        'label' => 'Test Backup',
        'user_id' => $this->user->id,
    ]);
});

test('user without create permission cannot create a backup destination', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-destinations']);

    $backupDestinationData = [
        'label' => 'Test Backup',
        'type' => 's3',
        's3_access_key' => 'test_access_key',
        's3_secret_key' => 'test_secret_key',
        's3_bucket_name' => 'test-bucket',
        'custom_s3_region' => 'us-west-2',
        'path_style_endpoint' => false,
    ];

    $response = $this->postJson('/api/backup-destinations', $backupDestinationData);

    $response->assertStatus(403);
});

test('user with view permission can view a specific backup destination', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-destinations']);

    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson("/api/backup-destinations/{$backupDestination->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'label', 'type', 'user_id', 'created_at', 'updated_at'],
        ])
        ->assertJsonFragment([
            'id' => $backupDestination->id,
            'label' => $backupDestination->label,
            'type' => $backupDestination->type,
        ])
        ->assertJsonMissing(['s3_access_key', 's3_secret_key']);
});

test('user without view permission cannot view a specific backup destination', function (): void {
    Sanctum::actingAs($this->user, ['create-backup-destinations']);

    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson("/api/backup-destinations/{$backupDestination->id}");

    $response->assertStatus(403);
});

test('user with update permission can update a backup destination', function (): void {
    Sanctum::actingAs($this->user, ['update-backup-destinations']);

    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'custom_s3',
    ]);

    $updatedData = [
        'label' => 'Updated Backup',
        'type' => 'custom_s3',
        'custom_s3_endpoint' => 'https://custom-s3.example.com',
        's3_access_key' => 'updated_access_key',
        's3_secret_key' => 'updated_secret_key',
        's3_bucket_name' => 'updated-bucket',
        'path_style_endpoint' => true,
    ];

    $response = $this->putJson("/api/backup-destinations/{$backupDestination->id}", $updatedData);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'label', 'type', 'user_id', 'created_at', 'updated_at'],
        ])
        ->assertJsonFragment([
            'label' => 'Updated Backup',
            'type' => 'custom_s3',
            's3_bucket_name' => 'updated-bucket',
            'custom_s3_endpoint' => 'https://custom-s3.example.com',
            'path_style_endpoint' => true,
        ])
        ->assertJsonMissing(['s3_access_key', 's3_secret_key']);

    $this->assertDatabaseHas('backup_destinations', [
        'id' => $backupDestination->id,
        'label' => 'Updated Backup',
        'type' => 'custom_s3',
    ]);
});

test('user without update permission cannot update a backup destination', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-destinations']);

    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $updatedData = [
        'label' => 'Updated Backup',
        'type' => 'custom_s3',
        'custom_s3_endpoint' => 'https://custom-s3.example.com',
        's3_access_key' => 'updated_access_key',
        's3_secret_key' => 'updated_secret_key',
        's3_bucket_name' => 'updated-bucket',
        'path_style_endpoint' => true,
    ];

    $response = $this->putJson("/api/backup-destinations/{$backupDestination->id}", $updatedData);

    $response->assertStatus(403);
});

test('user with delete permission can delete a backup destination', function (): void {
    Sanctum::actingAs($this->user, ['delete-backup-destinations']);

    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->deleteJson("/api/backup-destinations/{$backupDestination->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('backup_destinations', [
        'id' => $backupDestination->id,
    ]);
});

test('user without delete permission cannot delete a backup destination', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-destinations']);

    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->deleteJson("/api/backup-destinations/{$backupDestination->id}");

    $response->assertStatus(403);
});

test('it returns the correct backup destination for a valid ID', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-destinations']);

    $destination = BackupDestination::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson("/api/backup-destinations/{$destination->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'label', 'type', 'user_id', 'created_at', 'updated_at'],
        ])
        ->assertJsonFragment([
            'id' => $destination->id,
            'label' => $destination->label,
            'type' => $destination->type,
        ])
        ->assertJsonMissing(['s3_access_key', 's3_secret_key']);
});

test('it returns 404 for non-existent backup destination', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-destinations']);

    $nonExistentId = BackupDestination::max('id') + 1;

    $response = $this->getJson("/api/backup-destinations/{$nonExistentId}");

    $response->assertNotFound()
        ->assertJson(['message' => 'Backup destination not found']);
});

test('it returns 404 for backup destination with non-numeric ID', function (): void {
    Sanctum::actingAs($this->user, ['view-backup-destinations']);

    $response = $this->getJson('/api/backup-destinations/non-existent');

    $response->assertNotFound()
        ->assertJson(['message' => 'Backup destination not found']);
});

test('it returns 401 for unauthenticated user', function (): void {
    $destination = BackupDestination::factory()->create();

    $response = $this->getJson("/api/backup-destinations/{$destination->id}");

    $response->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('it returns 403 for user without view permission', function (): void {
    Sanctum::actingAs($this->user, ['create-backup-destinations']);

    $destination = BackupDestination::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson("/api/backup-destinations/{$destination->id}");

    $response->assertForbidden()
        ->assertJson(['message' => 'Unauthorized action.']);
});

test("it returns 403 for user trying to view another user's backup destination", function (): void {
    Sanctum::actingAs($this->user, ['view-backup-destinations']);

    $otherUser = User::factory()->create();
    $destination = BackupDestination::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->getJson("/api/backup-destinations/{$destination->id}");

    $response->assertForbidden();
});
