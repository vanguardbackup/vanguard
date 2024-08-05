<?php

declare(strict_types=1);

use App\Models\NotificationStream;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('user can list their notification streams', function (): void {
    Sanctum::actingAs($this->user, ['view-notification-streams']);

    NotificationStream::factory()->count(3)->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/notification-streams');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'user_id', 'label', 'type', 'value', 'receive_successful_backup_notifications', 'receive_failed_backup_notifications', 'created_at', 'updated_at'],
            ],
            'links',
            'meta',
        ]);
});

test('user cannot list notification streams without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $response = $this->getJson('/api/notification-streams');

    $response->assertStatus(403);
});

test('user can create a new notification stream', function (): void {
    Sanctum::actingAs($this->user, ['create-notification-streams']);

    $streamData = [
        'label' => 'Test Stream',
        'type' => NotificationStream::TYPE_EMAIL,
        'value' => 'test@example.com',
        'receive_successful_backup_notifications' => true,
        'receive_failed_backup_notifications' => true,
    ];

    $response = $this->postJson('/api/notification-streams', $streamData);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'label' => 'Test Stream',
            'type' => NotificationStream::TYPE_EMAIL,
            'value' => 'test@example.com',
            'receive_successful_backup_notifications' => true,
            'receive_failed_backup_notifications' => true,
        ]);

    $this->assertDatabaseHas('notification_streams', [
        'label' => 'Test Stream',
        'type' => NotificationStream::TYPE_EMAIL,
        'value' => 'test@example.com',
    ]);

    $stream = NotificationStream::where('label', 'Test Stream')->first();
    $this->assertNotNull($stream->receive_successful_backup_notifications);
    $this->assertNotNull($stream->receive_failed_backup_notifications);
});

test('user cannot create a notification stream without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $response = $this->postJson('/api/notification-streams', [
        'label' => 'Test Stream',
        'type' => NotificationStream::TYPE_EMAIL,
        'value' => 'test@example.com',
    ]);

    $response->assertStatus(403);
});

test('user can view a specific notification stream', function (): void {
    Sanctum::actingAs($this->user, ['view-notification-streams']);

    $stream = NotificationStream::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/notification-streams/{$stream->id}");

    $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $stream->id,
            'label' => $stream->label,
            'type' => $stream->type,
            'value' => $stream->value,
        ]);
});

test('user cannot view a notification stream without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $stream = NotificationStream::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/notification-streams/{$stream->id}");

    $response->assertStatus(403);
});

test('user can update their notification stream', function (): void {
    Sanctum::actingAs($this->user, ['update-notification-streams']);

    $stream = NotificationStream::factory()->create(['user_id' => $this->user->id]);

    $updatedData = [
        'label' => 'Updated Stream',
        'type' => NotificationStream::TYPE_SLACK,
        'value' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        'receive_successful_backup_notifications' => false,
        'receive_failed_backup_notifications' => true,
    ];

    $response = $this->putJson("/api/notification-streams/{$stream->id}", $updatedData);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'label' => 'Updated Stream',
            'type' => NotificationStream::TYPE_SLACK,
            'value' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
            'receive_successful_backup_notifications' => false,
            'receive_failed_backup_notifications' => true,
        ]);

    $this->assertDatabaseHas('notification_streams', [
        'id' => $stream->id,
        'label' => 'Updated Stream',
        'type' => NotificationStream::TYPE_SLACK,
        'value' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
    ]);

    $updatedStream = $stream->fresh();
    $this->assertNull($updatedStream->receive_successful_backup_notifications);
    $this->assertNotNull($updatedStream->receive_failed_backup_notifications);
});

test('user cannot update a notification stream without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $stream = NotificationStream::factory()->create(['user_id' => $this->user->id]);

    $response = $this->putJson("/api/notification-streams/{$stream->id}", [
        'label' => 'Updated Stream',
    ]);

    $response->assertStatus(403);
});

test('user can delete their notification stream', function (): void {
    Sanctum::actingAs($this->user, ['delete-notification-streams']);

    $stream = NotificationStream::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/notification-streams/{$stream->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('notification_streams', ['id' => $stream->id]);
});

test('user cannot delete a notification stream without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $stream = NotificationStream::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/notification-streams/{$stream->id}");

    $response->assertStatus(403);
});
