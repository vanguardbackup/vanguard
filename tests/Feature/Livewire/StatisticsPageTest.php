<?php

declare(strict_types=1);

use App\Livewire\StatisticsPage;
use App\Models\User;

test('the component renders successfully', function (): void {
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test(StatisticsPage::class);
    $component->assertOk();
});

test('the page can be loaded', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('statistics'));

    $response->assertOk();
});

test('guests cannot access this page', function (): void {
    $response = $this->get(route('statistics'));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});
