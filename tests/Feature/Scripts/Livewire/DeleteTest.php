<?php

declare(strict_types=1);

use App\Models\Script;
use App\Models\User;

test('the component can be rendered', function (): void {
    $script = Script::factory()->create();

    $livewire = Livewire::test('scripts.delete-script-button', ['script' => $script]);

    $livewire->assertOk();
});

test('A user can delete their own script', function (): void {
    $user = User::factory()->create();
    $script = Script::factory()->create([
        'user_id' => $user->id,
    ]);

    $livewire = Livewire::actingAs($user)->test('scripts.delete-script-button', ['script' => $script])
        ->call('delete');

    $this->assertDatabaseMissing('scripts', [
        'id' => $script->id,
    ]);

    $livewire->assertRedirect(route('scripts.index'));
    $this->assertAuthenticatedAs($user);
});

test('Another user cannot delete a users script', function (): void {
    $userOne = User::factory()->create();
    $userTwo = User::factory()->create();

    $script = Script::factory()->create(['user_id' => $userOne->id]);

    Livewire::actingAs($userTwo)
        ->test('scripts.delete-script-button', ['script' => $script])
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('scripts', [
        'id' => $script->id,
    ]);
});
