<?php

declare(strict_types=1);

use App\Livewire\Profile\APITokenManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the component can be rendered', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::actingAs($user)->test(APITokenManager::class)->assertOk();
});

test('the page can be visited by users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('profile.api'));

    $response
        ->assertOk()
        ->assertSeeLivewire('profile.api-token-manager');
});

test('the page cannot be visited by guests', function (): void {

    $response = $this->get(route('profile.api'));

    $response->assertRedirect('login');

    $this->assertGuest();
});

test('api tokens can be created', function (): void {

    $user = User::factory()->create();

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('permissions', [
            'read', 'update',
        ])
        ->call('createApiToken')
        ->assertHasNoErrors();

    $this->assertCount(1, $user->fresh()->tokens);

    $this->assertEquals('API Token', $user->fresh()->tokens->first()->name);
    $this->assertTrue($user->fresh()->tokens->first()->can('read'));
    $this->assertFalse($user->fresh()->tokens->first()->can('delete'));
});

test('api tokens can be deleted', function (): void {

    $this->actingAs($user = User::factory()->create());

    $token = $user->tokens()->create([
        'name' => 'Test Token',
        'token' => Str::random(40),
        'abilities' => ['create', 'read'],
    ]);

    Livewire::test(APITokenManager::class)
        ->set(['apiTokenIdBeingDeleted' => $token->id])
        ->call('deleteApiToken');

    $this->assertCount(0, $user->fresh()->tokens);
});
