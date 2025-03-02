<?php

declare(strict_types=1);

use App\Models\User;

test('the page is rendered by users', function (): void {

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('scripts.index'));

    $response->assertOk();

    $this->assertAuthenticatedAs($user);
});

test('the page is not rendered by guests', function (): void {

    $response = $this->get(route('scripts.index'));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});
