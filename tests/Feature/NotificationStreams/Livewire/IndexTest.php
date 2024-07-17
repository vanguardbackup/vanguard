<?php

declare(strict_types=1);

use App\Livewire\NotificationStreams\Index;
use App\Models\User;

test('the component renders successfully', function (): void {
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test(Index::class);
    $component->assertOk();
});

test('the page can be loaded', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('notification-streams.index'));

    $response->assertOk();
});
