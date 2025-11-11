<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Livewire\Volt\Volt;

test('confirm password screen can be rendered', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/confirm-password');

    $response
        ->assertSeeVolt('pages.auth.confirm-password')
        ->assertStatus(200);
});

test('password can be confirmed', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('pages.auth.confirm-password')
        ->set('password', 'password');

    $testable->call('confirmPassword');

    $testable
        ->assertRedirect('/overview')
        ->assertHasNoErrors();
});

test('password is not confirmed with invalid password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('pages.auth.confirm-password')
        ->set('password', 'wrong-password');

    $testable->call('confirmPassword');

    $testable
        ->assertNoRedirect()
        ->assertHasErrors('password');
});
