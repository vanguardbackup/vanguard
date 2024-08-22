<?php

declare(strict_types=1);

use App\Livewire\Profile\ConnectionsPage;
use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Toaster::fake();
});

it('can render the connections page', function (): void {
    Livewire::test(ConnectionsPage::class)
        ->assertViewIs('livewire.profile.connections-page')
        ->assertSeeLivewire('profile.connections-page');
});

it('shows GitHub connection option when configured', function (): void {
    Config::set('services.github.client_id', 'fake-client-id');
    Config::set('services.github.client_secret', 'fake-client-secret');

    Livewire::test(ConnectionsPage::class)
        ->assertSee('GitHub')
        ->assertSee('Connect');
});

it('shows GitLab connection option when configured', function (): void {
    Config::set('services.gitlab.client_id', 'fake-client-id');
    Config::set('services.gitlab.client_secret', 'fake-client-secret');

    Livewire::test(ConnectionsPage::class)
        ->assertSee('GitLab')
        ->assertSee('Connect');
});

it('hides connection options when not configured', function (): void {
    Config::set('services.github.client_id', null);
    Config::set('services.github.client_secret', null);
    Config::set('services.gitlab.client_id', null);
    Config::set('services.gitlab.client_secret', null);

    Livewire::test(ConnectionsPage::class)
        ->assertDontSee('GitHub')
        ->assertDontSee('GitLab');
});

it('shows disconnect button for connected services', function (): void {
    UserConnection::factory()->create([
        'user_id' => $this->user->id,
        'provider_name' => 'github',
    ]);

    Livewire::test(ConnectionsPage::class)
        ->assertSee('Disconnect')
        ->assertDontSee('Connect GitHub');
});

it('shows connect button for non-connected services', function (): void {
    Livewire::test(ConnectionsPage::class)
        ->assertSee('Connect')
        ->assertDontSee('Disconnect');
});

it('can initiate connection process', function (): void {
    $testable = Livewire::test(ConnectionsPage::class);

    $testable->call('connect', 'github')
        ->assertRedirect(route('github.redirect'));
});

it('can disconnect a service', function (): void {
    UserConnection::factory()->create([
        'user_id' => $this->user->id,
        'provider_name' => 'github',
    ]);

    Livewire::test(ConnectionsPage::class)
        ->call('disconnect', 'github')
        ->assertDontSee('Disconnect')
        ->assertSee('Connect');

    $this->assertDatabaseMissing('user_connections', [
        'user_id' => $this->user->id,
        'provider_name' => 'github',
    ]);

    Toaster::assertDispatched('Github account unlinked successfully!');
});

it('shows error when disconnecting non-existent service', function (): void {
    Livewire::test(ConnectionsPage::class)
        ->call('disconnect', 'github');

    Toaster::assertDispatched('No active connection found for github.');
});

it('shows refresh token button when refresh token exists', function (): void {
    UserConnection::factory()->create([
        'user_id' => $this->user->id,
        'provider_name' => 'github',
        'refresh_token' => 'fake-refresh-token',
    ]);

    Livewire::test(ConnectionsPage::class)
        ->assertSee('Refresh Token');
});

it('hides refresh token button when refresh token does not exist', function (): void {
    UserConnection::factory()->create([
        'user_id' => $this->user->id,
        'provider_name' => 'github',
        'refresh_token' => null,
    ]);

    Livewire::test(ConnectionsPage::class)
        ->assertDontSee('Refresh Token');
});

it('handles token refresh failure', function (): void {
    UserConnection::factory()->create([
        'user_id' => $this->user->id,
        'provider_name' => 'github',
        'refresh_token' => 'old-refresh-token',
    ]);

    Socialite::shouldReceive('driver->refreshToken')->andThrow(new Exception('Token refresh failed'));

    Livewire::test(ConnectionsPage::class)
        ->call('refresh', 'github');

    Toaster::assertDispatched('Failed to refresh token. Please try re-linking your account.');
});

it('handles invalid provider for connection', function (): void {
    Livewire::test(ConnectionsPage::class)
        ->call('connect', 'invalid-provider');

    Toaster::assertDispatched('Unsupported provider: invalid-provider');
});

it('handles invalid provider for disconnection', function (): void {
    Livewire::test(ConnectionsPage::class)
        ->call('disconnect', 'invalid-provider');

    Toaster::assertDispatched('No active connection found for invalid-provider.');
});

it('handles invalid provider for token refresh', function (): void {
    Livewire::test(ConnectionsPage::class)
        ->call('refresh', 'invalid-provider');

    Toaster::assertDispatched('Unable to refresh token. Please re-link your account.');
});

it('handles missing refresh token when refreshing', function (): void {
    UserConnection::factory()->create([
        'user_id' => $this->user->id,
        'provider_name' => 'github',
        'refresh_token' => null,
    ]);

    Livewire::test(ConnectionsPage::class)
        ->call('refresh', 'github');

    Toaster::assertDispatched('Unable to refresh token. Please re-link your account.');
});
