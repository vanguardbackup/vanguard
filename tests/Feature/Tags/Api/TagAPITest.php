<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('user can list their tags', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    Tag::factory()->count(3)->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/tags');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'user_id', 'label', 'description', 'created_at', 'updated_at'],
            ],
            'links',
            'meta',
        ]);
});

test('user cannot list tags without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $response = $this->getJson('/api/tags');

    $response->assertStatus(403);
});

test('user can create a new tag', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    $tagData = [
        'label' => 'New Tag',
        'description' => 'This is a new tag',
    ];

    $response = $this->postJson('/api/tags', $tagData);

    $response->assertStatus(201)
        ->assertJsonFragment($tagData);
});

test('user cannot create a tag without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $response = $this->postJson('/api/tags', [
        'label' => 'New Tag',
        'description' => 'This is a new tag',
    ]);

    $response->assertStatus(403);
});

test('user can view a specific tag', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    $tag = Tag::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/tags/{$tag->id}");

    $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $tag->id,
            'label' => $tag->label,
            'description' => $tag->description,
        ]);
});

test('user cannot view a tag without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $tag = Tag::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/tags/{$tag->id}");

    $response->assertStatus(403);
});

test('user can update their tag', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    $tag = Tag::factory()->create(['user_id' => $this->user->id]);

    $updatedData = [
        'label' => 'Updated Tag',
        'description' => 'This is an updated tag',
    ];

    $response = $this->putJson("/api/tags/{$tag->id}", $updatedData);

    $response->assertStatus(200)
        ->assertJsonFragment($updatedData);
});

test('user cannot update a tag without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $tag = Tag::factory()->create(['user_id' => $this->user->id]);

    $response = $this->putJson("/api/tags/{$tag->id}", [
        'label' => 'Updated Tag',
    ]);

    $response->assertStatus(403);
});

test('user can delete their tag', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    $tag = Tag::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/tags/{$tag->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});

test('user cannot delete a tag without proper permission', function (): void {
    Sanctum::actingAs($this->user, []);

    $tag = Tag::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/tags/{$tag->id}");

    $response->assertStatus(403);
});

// New tests for 404 and other edge cases

test('viewing a non-existent tag returns 404', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    $nonExistentId = 9999;
    $response = $this->getJson("/api/tags/{$nonExistentId}");

    $response->assertStatus(404);
});

test('updating a non-existent tag returns 404', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    $nonExistentId = 9999;
    $response = $this->putJson("/api/tags/{$nonExistentId}", [
        'label' => 'Updated Tag',
    ]);

    $response->assertStatus(404);
});

test('deleting a non-existent tag returns 404', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    $nonExistentId = 9999;
    $response = $this->deleteJson("/api/tags/{$nonExistentId}");

    $response->assertStatus(404);
});

test('user cannot view a tag belonging to another user', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    $anotherUser = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->getJson("/api/tags/{$tag->id}");

    $response->assertStatus(403);
});

test('user cannot update a tag belonging to another user', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    $anotherUser = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->putJson("/api/tags/{$tag->id}", [
        'label' => 'Updated Tag',
    ]);

    $response->assertStatus(403);
});

test('user cannot delete a tag belonging to another user', function (): void {
    Sanctum::actingAs($this->user, ['manage-tags']);

    $anotherUser = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->deleteJson("/api/tags/{$tag->id}");

    $response->assertStatus(403);
});
