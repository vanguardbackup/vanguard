<?php

declare(strict_types=1);

use App\Console\Commands\DisableTwoFactorAuth;
use App\Models\User;

test('an email address is required', function (): void {

    $this->artisan(DisableTwoFactorAuth::class, ['email' => null])
        ->assertExitCode(0);
});

test('an email address must exist', function (): void {

    $this->artisan(DisableTwoFactorAuth::class, ['email' => 'foo@bar.com'])
        ->expectsOutputToContain("A user cannot be found with the email address 'foo@bar.com'")
        ->assertExitCode(0);
});

test('a user must have two-factor authentication enabled', function (): void {
    $user = User::factory()->create();

    $this->artisan(DisableTwoFactorAuth::class, ['email' => $user->email])
        ->expectsOutputToContain("{$user->name} has not enabled two-factor authentication.")
        ->assertExitCode(0);

    $this->assertFalse($user->hasTwoFactorEnabled());
});

test('it disables two-factor auth for a user', function (): void {
    $user = User::factory()->create();
    $user->createTwoFactorAuth();
    $user->enableTwoFactorAuth();

    $this->artisan(DisableTwoFactorAuth::class, ['email' => $user->email])
        ->expectsOutputToContain("Disabled two-factor authentication for {$user->name}.")
        ->assertExitCode(0);

    $user = $user->fresh();

    $this->assertFalse($user->hasTwoFactorEnabled());
});
