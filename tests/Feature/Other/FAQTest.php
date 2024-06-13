<?php

use App\Models\User;

test('the faq page can be rendered by users', function () {

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('frequently-asked-questions'))
        ->assertOk();

    $this->assertAuthenticatedAs($user);
});

test('the faq page cannot be rendered by guests', function () {

    $this->get(route('frequently-asked-questions'))
        ->assertRedirect('login');
});
