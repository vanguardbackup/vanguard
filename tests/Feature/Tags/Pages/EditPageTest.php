<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;

test('the page can be rendered by by the owner of the tag', function (): void {

    $user = User::factory()->create();

    $tag = Tag::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('tags.edit', $tag));

    $response->assertOk();
    $response->assertViewIs('tags.edit');
    $response->assertViewHas('tag', $tag);

    $this->assertAuthenticatedAs($user);
    $this->assertEquals($user->id, $tag->user_id);
});

test('the page is not rendered by unauthorized users', function (): void {

    $user = User::factory()->create();

    $tag = Tag::factory()->create();

    $response = $this->actingAs($user)->get(route('tags.edit', $tag));

    $response->assertForbidden();

    $this->assertAuthenticatedAs($user);

    $this->assertNotEquals($user->id, $tag->user_id);
});

test('the page is not rendered by guests', function (): void {

    $tag = Tag::factory()->create();

    $response = $this->get(route('tags.edit', $tag));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});
