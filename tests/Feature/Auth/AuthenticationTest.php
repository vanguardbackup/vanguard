<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Volt\Volt;

test('login screen can be rendered', function (): void {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');
});

test('users can authenticate using the login screen', function (): void {
    $user = User::factory()->create();

    $testable = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $testable->call('login');

    $testable
        ->assertHasNoErrors()
        ->assertRedirect(route('overview', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function (): void {
    $user = User::factory()->create();

    $testable = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');

    $testable->call('login');

    $testable
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

test('navigation menu can be rendered', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/overview');

    $response
        ->assertOk()
        ->assertSeeVolt('layout.navigation');
});

test('users can logout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('layout.navigation');

    $testable->call('logout');

    $testable
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
});
