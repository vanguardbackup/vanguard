<?php

declare(strict_types=1);

use App\Models\User;

test('the component can be rendered', function (): void {

    $livewire = Livewire::test('tags.create-form');

    $livewire->assertOk();
});

test('a user can create a new tag', function (): void {

    $user = User::factory()->create();

    $livewire = Livewire::actingAs($user)
        ->test('tags.create-form')
        ->set('label', 'New Tag')
        ->set('description', 'This is a new tag')
        ->call('submit');

    $this->assertDatabaseHas('tags', [
        'label' => 'New Tag',
        'description' => 'This is a new tag',
        'user_id' => $user->id,
    ]);

    $livewire->assertRedirect(route('tags.index'));
});

test('a label is required', function (): void {

    $user = User::factory()->create();

    $livewire = Livewire::actingAs($user)
        ->test('tags.create-form')
        ->set('label', '')
        ->call('submit')
        ->assertHasErrors(['label' => 'required']);

    $this->assertDatabaseCount('tags', 0);
});
