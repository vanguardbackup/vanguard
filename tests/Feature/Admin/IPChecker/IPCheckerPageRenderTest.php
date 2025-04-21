<?php

declare(strict_types=1);

use App\Models\User;

test('guests cannot render this page', function (): void {

    $response = $this->get(route('admin.ip-checker'));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('users cannot render this page', function (): void {

    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('admin.ip-checker'));

    $response->assertNotFound();

    $this->assertAuthenticatedAs($user);
});

test('admins can render this page', function (): void {

    Config::set('auth.admin_email_addresses', ['admin@email.com']);

    $user = User::factory()->create(['email' => 'admin@email.com']);

    $response = $this->actingAs($user)->get(route('admin.ip-checker'));

    $response->assertOk();

    $this->assertAuthenticated();

    $this->assertTrue($user->isAdmin());
});
