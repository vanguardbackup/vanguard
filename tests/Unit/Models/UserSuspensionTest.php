<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserSuspension;

test('it correctly scopes active suspensions', function (): void {
    $user = User::factory()->create();

    $now = now();
    $yesterday = $now->copy()->subDay();
    $tomorrow = $now->copy()->addDay();
    $lastWeek = $now->copy()->subWeek();

    // Case 1: Indefinite suspension (no end date)
    $indefiniteSuspension = UserSuspension::factory()->create([
        'user_id' => $user->id,
        'suspended_at' => $yesterday,
        'suspended_until' => null,
        'lifted_at' => null,
    ]);

    // Case 2: Future end date
    $futureSuspension = UserSuspension::factory()->create([
        'user_id' => $user->id,
        'suspended_at' => $yesterday,
        'suspended_until' => $tomorrow,
        'lifted_at' => null,
    ]);

    // Create inactive suspensions - should NOT be included in scope
    // Case 3: Already lifted
    $liftedSuspension = UserSuspension::factory()->create([
        'user_id' => $user->id,
        'suspended_at' => $lastWeek,
        'suspended_until' => $tomorrow,
        'lifted_at' => $yesterday,
    ]);

    // Case 4: Already expired
    $expiredSuspension = UserSuspension::factory()->create([
        'user_id' => $user->id,
        'suspended_at' => $lastWeek,
        'suspended_until' => $yesterday,
        'lifted_at' => null,
    ]);

    // Get active suspensions
    $activeSuspensions = UserSuspension::active()->get();

    // Assertions
    expect($activeSuspensions)->toHaveCount(2)
        ->and($activeSuspensions->pluck('id')->toArray())->toContain($indefiniteSuspension->id)
        ->and($activeSuspensions->pluck('id')->toArray())->toContain($futureSuspension->id)
        ->and($activeSuspensions->pluck('id')->toArray())->not->toContain($liftedSuspension->id)
        ->and($activeSuspensions->pluck('id')->toArray())->not->toContain($expiredSuspension->id);

    // Test filtering for a specific user
    $anotherUser = User::factory()->create();
    $anotherUserActiveSuspension = UserSuspension::factory()->create([
        'user_id' => $anotherUser->id,
        'suspended_at' => $yesterday,
        'suspended_until' => $tomorrow,
        'lifted_at' => null,
    ]);

    $specificUserActiveSuspensions = UserSuspension::active()->where('user_id', $user->id)->get();
    expect($specificUserActiveSuspensions)->toHaveCount(2)
        ->and($specificUserActiveSuspensions->pluck('id')->toArray())->not->toContain($anotherUserActiveSuspension->id);
});
