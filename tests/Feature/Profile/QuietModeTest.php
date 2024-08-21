<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

beforeEach(function (): void {
    $this->user = User::factory()->create(['password' => Hash::make('password')]);
    $this->actingAs($this->user);
});

test('the component can be rendered', function (): void {
    Volt::test('profile.quiet-mode-manager')
        ->assertOk();
});

test('the page can be visited by authenticated users', function (): void {
    $this->get(route('profile.quiet-mode'))
        ->assertOk()
        ->assertSeeLivewire('profile.quiet-mode-manager');
});

test('the page cannot be visited by guests', function (): void {
    Auth::logout();
    $this->get(route('profile.quiet-mode'))
        ->assertRedirect('login');
    $this->assertGuest();
});

test('quiet mode can be enabled', function (): void {
    $futureDate = now()->addDays(5)->format('Y-m-d');

    Volt::test('profile.quiet-mode-manager')
        ->set('quietUntilDate', $futureDate)
        ->call('enableQuietMode')
        ->assertHasNoErrors()
        ->assertSet('isQuietModeActive', true);

    $this->user->refresh();
    expect($this->user->quiet_until->format('Y-m-d'))->toBe($futureDate);
});

test('quiet mode can be disabled', function (): void {
    $this->user->update(['quiet_until' => now()->addDays(5)]);

    Volt::test('profile.quiet-mode-manager')
        ->call('disableQuietMode')
        ->assertHasNoErrors()
        ->assertSet('isQuietModeActive', false);

    $this->user->refresh();
    expect($this->user->quiet_until)->toBeNull();
});

test('quiet mode cannot be enabled with a past date', function (): void {
    $pastDate = now()->subDay()->format('Y-m-d');

    Volt::test('profile.quiet-mode-manager')
        ->set('quietUntilDate', $pastDate)
        ->call('enableQuietMode')
        ->assertHasErrors(['quietUntilDate']);
});
