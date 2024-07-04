<?php

declare(strict_types=1);

use App\Models\User;

test('the faq page can be rendered by users', function (): void {

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('frequently-asked-questions'))
        ->assertOk();

    $this->assertAuthenticatedAs($user);
});

test('the faq page cannot be rendered by guests', function (): void {

    $this->get(route('frequently-asked-questions'))
        ->assertRedirect('login');
});
