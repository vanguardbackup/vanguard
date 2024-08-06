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
                '*' => [
                    'id',
                    'user_id',
                    'label',
                    'type',
                    'notifications' => [
                        'on_success',
                        'on_failure',
                    ],
                    'created_at',
                    'updated_at',
                ],
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
        'notifications' => [
            'on_success' => true,
            'on_failure' => true,
        ],
    ];

    $response = $this->postJson('/api/notification-streams', $streamData);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'label' => 'Test Stream',
            'type' => NotificationStream::TYPE_EMAIL,
            'notifications' => [
                'on_success' => true,
                'on_failure' => true,
            ],
        ]);

    $this->assertDatabaseHas('notification_streams', [
        'label' => 'Test Stream',
        'type' => NotificationStream::TYPE_EMAIL,
        'value' => 'test@example.com',
    ]);
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
        'notifications' => [
            'on_success' => false,
            'on_failure' => true,
        ],
    ];

    $response = $this->putJson("/api/notification-streams/{$stream->id}", $updatedData);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'label' => 'Updated Stream',
            'type' => NotificationStream::TYPE_SLACK,
            'notifications' => [
                'on_success' => false,
                'on_failure' => true,
            ],
        ]);

    $this->assertDatabaseHas('notification_streams', [
        'id' => $stream->id,
        'label' => 'Updated Stream',
        'type' => NotificationStream::TYPE_SLACK,
        'value' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
    ]);
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

test('viewing a non-existent notification stream returns 404', function (): void {
    Sanctum::actingAs($this->user, ['view-notification-streams']);

    $nonExistentId = 9999;
    $response = $this->getJson("/api/notification-streams/{$nonExistentId}");

    $response->assertStatus(404);
});

test('updating a non-existent notification stream returns 404', function (): void {
    Sanctum::actingAs($this->user, ['update-notification-streams']);

    $nonExistentId = 9999;
    $response = $this->putJson("/api/notification-streams/{$nonExistentId}", [
        'label' => 'Updated Stream',
    ]);

    $response->assertStatus(404);
});

test('deleting a non-existent notification stream returns 404', function (): void {
    Sanctum::actingAs($this->user, ['delete-notification-streams']);

    $nonExistentId = 9999;
    $response = $this->deleteJson("/api/notification-streams/{$nonExistentId}");

    $response->assertStatus(404);
});

test('user cannot view a notification stream belonging to another user', function (): void {
    Sanctum::actingAs($this->user, ['view-notification-streams']);

    $anotherUser = User::factory()->create();
    $stream = NotificationStream::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->getJson("/api/notification-streams/{$stream->id}");

    $response->assertStatus(403);
});

test('user cannot update a notification stream belonging to another user', function (): void {
    Sanctum::actingAs($this->user, ['update-notification-streams']);

    $anotherUser = User::factory()->create();
    $stream = NotificationStream::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->putJson("/api/notification-streams/{$stream->id}", [
        'label' => 'Updated Stream',
    ]);

    $response->assertStatus(403);
});

test('user cannot delete a notification stream belonging to another user', function (): void {
    Sanctum::actingAs($this->user, ['delete-notification-streams']);

    $anotherUser = User::factory()->create();
    $stream = NotificationStream::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->deleteJson("/api/notification-streams/{$stream->id}");

    $response->assertStatus(403);
});
