<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

beforeEach(function (): void {
    $this->user = User::factory()->create(['password' => Hash::make('password')]);
    $this->actingAs($this->user);
    Config::set('session.driver', 'database');
});

test('the component can be rendered', function (): void {
    Volt::test('profile.session-manager')
        ->assertOk();
});

test('the page can be visited by authenticated users', function (): void {
    $this->get(route('profile.sessions'))
        ->assertOk()
        ->assertSeeLivewire('profile.session-manager');
});

test('the page cannot be visited by guests', function (): void {
    Auth::logout();
    $this->get(route('profile.sessions'))
        ->assertRedirect('login');
    $this->assertGuest();
});

test('it shows a warning when session driver is not database', function (): void {
    Config::set('session.driver', 'file');

    Volt::test('profile.session-manager')
        ->assertSee('The session driver is not configured to use the database');
});

test('it requires correct password to logout other browser sessions', function (): void {
    Volt::test('profile.session-manager')
        ->set('password', 'wrong_password')
        ->call('logoutOtherBrowserSessions')
        ->assertHasErrors(['password']);
});
