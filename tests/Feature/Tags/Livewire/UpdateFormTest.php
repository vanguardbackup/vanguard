<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;

test('the component can be rendered', function (): void {

    $tag = Tag::factory()->create();

    $livewire = Livewire::test('tags.update-form', ['tag' => $tag]);

    $livewire->assertOk();
});

test('A user can update their own tag', function (): void {

    $user = User::factory()->create();

    $tag = Tag::factory()->create(['user_id' => $user->id, 'label' => 'Old Tag', 'description' => 'Old Description']);

    $livewire = Livewire::actingAs($user)
        ->test('tags.update-form', ['tag' => $tag])
        ->set('label', 'New Tag')
        ->set('description', 'New Description')
        ->call('submit');

    $this->assertDatabaseHas('tags', [
        'label' => 'New Tag',
        'description' => 'New Description',
        'user_id' => $user->id,
    ]);

    $livewire->assertRedirect(route('tags.index'));
});

test('Another user cannot update a users tag', function (): void {

    $userOne = User::factory()->create();
    $userTwo = User::factory()->create();

    $tag = Tag::factory()->create(['user_id' => $userOne->id, 'label' => 'Old Tag', 'description' => 'Old Description']);

    $livewire = Livewire::actingAs($userTwo)
        ->test('tags.update-form', ['tag' => $tag])
        ->set('label', 'New Tag')
        ->set('description', 'New Description')
        ->call('submit')
        ->assertForbidden();

    $this->assertDatabaseHas('tags', [
        'label' => 'Old Tag',
        'description' => 'Old Description',
        'user_id' => $userOne->id,
    ]);
});

test('a label is required', function (): void {

    $user = User::factory()->create();

    $tag = Tag::factory()->create(['user_id' => $user->id]);

    $livewire = Livewire::actingAs($user)
        ->test('tags.update-form', ['tag' => $tag])
        ->set('label', '')
        ->call('submit')
        ->assertHasErrors(['label' => 'required']);

    $this->assertDatabaseHas('tags', [
        'label' => $tag->label,
        'user_id' => $user->id,
    ]);
});
