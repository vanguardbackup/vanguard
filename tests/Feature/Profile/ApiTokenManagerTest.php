<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('the component can be rendered', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('profile.api-token-manager')
        ->assertOk();
});

test('the page can be visited by authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile.api'))
        ->assertOk()
        ->assertSeeLivewire('profile.api-token-manager');
});

test('the page cannot be visited by guests', function (): void {
    $this->get(route('profile.api'))
        ->assertRedirect('login');

    $this->assertGuest();
});

test('api tokens can be created with correct abilities format', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('profile.api-token-manager')
        ->set('name', 'API Token')
        ->set('abilities', [
            'view-backup-destinations' => true,
            'create-backup-tasks' => true,
            'manage-tags' => false,
        ])
        ->call('createApiToken')
        ->assertHasNoErrors()
        ->assertDispatched('created');

    $this->assertCount(1, $user->fresh()->tokens);

    $token = $user->fresh()->tokens->first();
    $this->assertEquals('API Token', $token->name);
    $this->assertEquals(['view-backup-destinations', 'create-backup-tasks'], $token->abilities);
});

test('api tokens cannot be created without abilities', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('profile.api-token-manager')
        ->set('name', 'API Token')
        ->set('abilities', [])
        ->call('createApiToken')
        ->assertHasErrors(['abilities']);

    $this->assertCount(0, $user->fresh()->tokens);
});

test('api tokens can be deleted', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Test Token', ['view-backup-destinations']);

    $this->actingAs($user);

    Volt::test('profile.api-token-manager')
        ->set('apiTokenIdBeingDeleted', $token->accessToken->id)
        ->call('deleteApiToken');

    $this->assertCount(0, $user->fresh()->tokens);
});

test('api token deletion confirmation works', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Test Token', ['view-backup-destinations']);

    $this->actingAs($user);

    Volt::test('profile.api-token-manager')
        ->call('confirmApiTokenDeletion', $token->accessToken->id)
        ->assertDispatched('open-modal', 'confirm-api-token-deletion')
        ->assertSet('apiTokenIdBeingDeleted', $token->accessToken->id);
});

test('abilities are reset after token creation', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.api-token-manager')
        ->set('name', 'API Token')
        ->set('abilities', [
            'view-backup-destinations' => true,
            'create-backup-tasks' => true,
        ])
        ->call('createApiToken');

    $testable->assertSet('abilities', array_fill_keys(array_keys($testable->get('abilities')), false));
});

test('token value is displayed after creation', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.api-token-manager')
        ->set('name', 'API Token')
        ->set('abilities', ['view-backup-destinations' => true])
        ->call('createApiToken');

    $testable
        ->assertDispatched('close-modal', 'create-api-token')
        ->assertDispatched('open-modal', 'api-token-value')
        ->assertSet('plainTextToken', function ($plainTextToken): bool {
            return ! empty($plainTextToken) && is_string($plainTextToken);
        });
});

test('all available abilities are initially set to false', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.api-token-manager');

    $abilities = $testable->get('abilities');
    foreach ($abilities as $ability) {
        expect($ability)->toBeFalse();
    }
});

test('validation error occurs when no abilities are selected', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('profile.api-token-manager')
        ->set('name', 'API Token')
        ->set('abilities', [
            'view-backup-destinations' => false,
            'create-backup-tasks' => false,
            'manage-tags' => false,
        ])
        ->call('createApiToken')
        ->assertHasErrors(['abilities']);
});

test('select all abilities works', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.api-token-manager')
        ->call('selectAllAbilities');

    $abilities = $testable->get('abilities');
    foreach ($abilities as $ability) {
        expect($ability)->toBeTrue();
    }
});

test('deselect all abilities works', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.api-token-manager')
        ->set('abilities', [
            'view-backup-destinations' => true,
            'create-backup-tasks' => true,
            'manage-tags' => true,
        ])
        ->call('deselectAllAbilities');

    $abilities = $testable->get('abilities');
    foreach ($abilities as $ability) {
        expect($ability)->toBeFalse();
    }
});

test('view token abilities modal can be opened', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Test Token', ['view-backup-destinations']);

    $this->actingAs($user);

    Volt::test('profile.api-token-manager')
        ->call('viewTokenAbilities', $token->accessToken->id)
        ->assertDispatched('open-modal', 'view-token-abilities')
        ->assertSet('viewingTokenId', $token->accessToken->id);
});

test('token listing shows correct information', function (): void {
    $user = User::factory()->create();
    $user->createToken('Test Token', ['view-backup-destinations']);

    $this->actingAs($user);

    Volt::test('profile.api-token-manager')
        ->assertSee('Test Token')
        ->assertSee('Never'); // For 'Last Used' column
});

test('group expansion toggle works', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.api-token-manager');

    $testable->call('toggleGroup', 'General')
        ->assertSet('expandedGroups.General', true)
        ->call('toggleGroup', 'General')
        ->assertSet('expandedGroups.General', false);
});

test('api tokens can be created with custom expiration date', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $expirationDate = now()->addMonths(3)->format('Y-m-d');

    Volt::test('profile.api-token-manager')
        ->set('name', 'Custom Expiration Token')
        ->set('abilities', ['view-backup-destinations' => true])
        ->set('expirationOption', 'custom')
        ->set('customExpirationDate', $expirationDate)
        ->call('createApiToken')
        ->assertHasNoErrors()
        ->assertDispatched('created');

    $token = $user->fresh()->tokens->first();
    expect($token->name)->toBe('Custom Expiration Token')
        ->and($token->expires_at->format('Y-m-d'))->toBe($expirationDate);
});

test('api tokens cannot be created with invalid custom expiration date', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('profile.api-token-manager')
        ->set('name', 'Invalid Expiration Token')
        ->set('abilities', ['view-backup-destinations' => true])
        ->set('expirationOption', 'custom')
        ->set('customExpirationDate', now()->subDay()->format('Y-m-d'))
        ->call('createApiToken')
        ->assertHasErrors(['customExpirationDate']);
});

test('api tokens can be created with predefined expiration options', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $expirationOptions = ['1_month', '6_months', '1_year'];

    foreach ($expirationOptions as $expirationOption) {
        $component = Volt::test('profile.api-token-manager')
            ->set('name', "Token with {$expirationOption} expiration")
            ->set('abilities', ['view-backup-destinations'])
            ->set('expirationOption', $expirationOption);

        $component->call('createApiToken')
            ->assertHasNoErrors()
            ->assertDispatched('created');

        $token = $user->fresh()->tokens->sortByDesc('created_at')->first();
        expect($token->name)->toBe("Token with {$expirationOption} expiration");

        $expectedExpiration = match ($expirationOption) {
            '1_month' => now()->addMonth(),
            '6_months' => now()->addMonths(6),
            '1_year' => now()->addYear(),
        };

        expect($token->expires_at->startOfDay())->toEqual($expectedExpiration->startOfDay());

        $token->delete();
    }
});

test('api tokens can be created with no expiration', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('profile.api-token-manager')
        ->set('name', 'Never Expiring Token')
        ->set('abilities', ['view-backup-destinations' => true])
        ->set('expirationOption', 'never')
        ->call('createApiToken')
        ->assertHasNoErrors()
        ->assertDispatched('created');

    $token = $user->fresh()->tokens->first();
    expect($token->name)->toBe('Never Expiring Token')
        ->and($token->expires_at)->toBeNull();
});

test('custom expiration date cannot be more than 5 years in the future', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $farFutureDate = now()->addYears(6)->format('Y-m-d');

    Volt::test('profile.api-token-manager')
        ->set('name', 'Far Future Token')
        ->set('abilities', ['view-backup-destinations' => true])
        ->set('expirationOption', 'custom')
        ->set('customExpirationDate', $farFutureDate)
        ->call('createApiToken')
        ->assertHasErrors(['customExpirationDate']);
});
