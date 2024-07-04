<?php

declare(strict_types=1);

use App\Models\User;

test('the page is rendered by users', function () {

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('remote-servers.index'));

    $response->assertOk();

    $this->assertAuthenticatedAs($user);
});

test('the page is not rendered by guests', function () {

    $response = $this->get(route('remote-servers.index'));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});
