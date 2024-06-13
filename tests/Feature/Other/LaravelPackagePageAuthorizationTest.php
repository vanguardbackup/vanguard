<?php

use App\Models\User;

test('guests cannot access Laravel Pulse', function () {

    $response = $this->get('/pulse');

    $response->assertForbidden();

    $this->assertGuest();
});

test('users cannot access Laravel Pulse', function () {

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/pulse');

    $response->assertForbidden();

    $this->assertAuthenticated();
});

test('admins can access Laravel Pulse', function () {

    Config::set('auth.admin_email_addresses', ['admin@email.com']);

    $user = User::factory()->create(['email' => 'admin@email.com']);

    $response = $this->actingAs($user)->get('/pulse');

    $response->assertOk();

    $this->assertAuthenticated();

    $this->assertTrue($user->isAdmin());
});

test('guests cannot access Laravel Horizon', function () {

    $response = $this->get('/horizon');

    $response->assertForbidden();

    $this->assertGuest();
});

test('users cannot access Laravel Horizon', function () {

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/horizon');

    $response->assertForbidden();

    $this->assertAuthenticated();
});

test('admins can access Laravel Horizon', function () {

    Config::set('auth.admin_email_addresses', ['admin@email.com']);

    $user = User::factory()->create(['email' => 'admin@email.com']);

    $response = $this->actingAs($user)->get('/horizon');

    $response->assertOk();

    $this->assertAuthenticated();

    $this->assertTrue($user->isAdmin());
});
