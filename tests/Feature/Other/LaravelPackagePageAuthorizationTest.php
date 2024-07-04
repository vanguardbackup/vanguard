<?php

declare(strict_types=1);

use App\Models\User;

test('guests cannot access Laravel Pulse', function (): void {

    $response = $this->get('/pulse');

    $response->assertForbidden();

    $this->assertGuest();
});

test('users cannot access Laravel Pulse', function (): void {

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/pulse');

    $response->assertForbidden();

    $this->assertAuthenticated();
});

test('admins can access Laravel Pulse', function (): void {

    Config::set('auth.admin_email_addresses', ['admin@email.com']);

    $user = User::factory()->create(['email' => 'admin@email.com']);

    $response = $this->actingAs($user)->get('/pulse');

    $response->assertOk();

    $this->assertAuthenticated();

    $this->assertTrue($user->isAdmin());
});

test('guests cannot access Laravel Horizon', function (): void {

    $response = $this->get('/horizon');

    $response->assertForbidden();

    $this->assertGuest();
});

test('users cannot access Laravel Horizon', function (): void {

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/horizon');

    $response->assertForbidden();

    $this->assertAuthenticated();
});

test('admins can access Laravel Horizon', function (): void {

    Config::set('auth.admin_email_addresses', ['admin@email.com']);

    $user = User::factory()->create(['email' => 'admin@email.com']);

    $response = $this->actingAs($user)->get('/horizon');

    $response->assertOk();

    $this->assertAuthenticated();

    $this->assertTrue($user->isAdmin());
});
