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
        ->set('colour', '#99ccff')
        ->call('submit');

    $this->assertDatabaseHas('tags', [
        'label' => 'New Tag',
        'description' => 'This is a new tag',
        'colour' => '#99ccff',
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

test('a colour must be a valid colour', function (): void {

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('tags.create-form')
        ->set('label', 'My first tag!')
        ->set('colour', '#123-not-a-valid-colour')
        ->call('submit')
        ->assertHasErrors(['colour']);

    $this->assertDatabaseCount('tags', 0);
});
