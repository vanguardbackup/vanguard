<?php

use App\Models\Tag;
use App\Models\User;

test('the component can be rendered', function () {

    $tag = Tag::factory()->create();

    $livewire = Livewire::test('tags.delete-tag-button', ['tag' => $tag]);

    $livewire->assertOk();
});

test('A user can delete their own tag', function () {

    $user = User::factory()->create();
    $tag = Tag::factory()->create([
        'user_id' => $user->id,
    ]);

    $livewire = Livewire::actingAs($user)->test('tags.delete-tag-button', ['tag' => $tag])
        ->call('delete');

    $this->assertDatabaseMissing('tags', [
        'id' => $tag->id,
    ]);

    $livewire->assertRedirect(route('tags.index'));
    $this->assertAuthenticatedAs($user);
});

test('Another user cannot delete a users tag', function () {

    $userOne = User::factory()->create();
    $userTwo = User::factory()->create();

    $tag = Tag::factory()->create(['user_id' => $userOne->id]);

    $livewire = Livewire::actingAs($userTwo)
        ->test('tags.delete-tag-button', ['tag' => $tag])
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('tags', [
        'id' => $tag->id,
    ]);
});
