<?php

declare(strict_types=1);

use App\Livewire\Profile\APITokenManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the component can be rendered', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(APITokenManager::class)->assertOk();
});

test('the page can be visited by authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.api'));

    $response
        ->assertOk()
        ->assertSeeLivewire('profile.api-token-manager');
});

test('the page cannot be visited by guests', function (): void {
    $response = $this->get(route('profile.api'));

    $response->assertRedirect('login');
    $this->assertGuest();
});

test('api tokens can be created with correct permissions format', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('permissions', [
            'view-backup-destinations' => true,
            'create-backup-tasks' => true,
            'update-remote-servers' => false,
        ])
        ->call('createApiToken')
        ->assertHasNoErrors()
        ->assertDispatched('created');

    $this->assertCount(1, $user->fresh()->tokens);

    $token = $user->fresh()->tokens->first();
    $this->assertEquals('API Token', $token->name);
    $this->assertEquals(['view-backup-destinations', 'create-backup-tasks'], $token->abilities);
});

test('api tokens cannot be created without permissions', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('permissions', [])
        ->call('createApiToken')
        ->assertHasErrors(['permissions']);

    $this->assertCount(0, $user->fresh()->tokens);
});

test('api tokens can be deleted', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Test Token', ['view-backup-destinations']);

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('apiTokenIdBeingDeleted', $token->accessToken->id)
        ->call('deleteApiToken');

    $this->assertCount(0, $user->fresh()->tokens);
});

test('api token deletion confirmation works', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Test Token', ['view-backup-destinations']);

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->call('confirmApiTokenDeletion', $token->accessToken->id)
        ->assertDispatched('open-modal', 'confirm-api-token-deletion')
        ->assertSet('apiTokenIdBeingDeleted', $token->accessToken->id);
});

test('permissions are reset after token creation', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('permissions', [
            'view-backup-destinations' => true,
            'create-backup-tasks' => true,
        ])
        ->call('createApiToken');

    $component->assertSet('permissions', array_fill_keys(array_keys($component->get('permissions')), false));
});

test('token value is displayed after creation', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('permissions', ['view-backup-destinations' => true])
        ->call('createApiToken');

    $component
        ->assertDispatched('close-modal', 'create-api-token')
        ->assertDispatched('open-modal', 'api-token-value')
        ->assertSet('plainTextToken', function ($plainTextToken): bool {
            return ! empty($plainTextToken) && is_string($plainTextToken);
        });
});

test('all available permissions are initially set to false', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(APITokenManager::class);

    $permissions = $component->get('permissions');
    foreach ($permissions as $permission) {
        expect($permission)->toBeFalse();
    }
});

test('validation error occurs when no permissions are selected', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('permissions', array_fill_keys(array_keys((new APITokenManager)->getPermissions()), false))
        ->call('createApiToken')
        ->assertHasErrors(['permissions']);
});
