<?php

declare(strict_types=1);

use App\Console\Commands\DisableUserAccount;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Exception\RuntimeException;

test('a user ID is required', function (): void {
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Not enough arguments (missing: "user").');

    $this->artisan(DisableUserAccount::class);
});

test('a user must exist', function (): void {
    $this->artisan(DisableUserAccount::class, ['user' => 999])
        ->expectsOutputToContain('User with ID 999 not found.')
        ->assertExitCode(1);
});

test('an admin account cannot be disabled', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $user = User::factory()->create(['email' => 'admin@email.com', 'account_disabled_at' => null]);

    $this->artisan(DisableUserAccount::class, ['user' => $user->id])
        ->expectsOutputToContain('Cannot disable an admin account.')
        ->assertExitCode(1);

    $this->assertNull($user->fresh()->account_disabled_at);
});

test('it returns success if the account is already disabled', function (): void {
    $user = User::factory()->create(['account_disabled_at' => now()]);

    $this->artisan(DisableUserAccount::class, ['user' => $user->id])
        ->expectsOutputToContain('User account is already disabled.')
        ->assertExitCode(0);
});

test('it disables a user account and clears sessions', function (): void {
    $user = User::factory()->create();

    // Simulate a session for the user
    DB::table('sessions')->insert([
        'id' => 'test_session_id',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'payload' => 'test_payload',
        'last_activity' => now()->timestamp,
    ]);

    $this->artisan(DisableUserAccount::class, ['user' => $user->id])
        ->expectsOutputToContain('User account has been disabled and all sessions cleared.')
        ->assertExitCode(0);

    $user->refresh();
    $this->assertNotNull($user->account_disabled_at);
});

test('it warns about unsupported session drivers', function (): void {
    $user = User::factory()->create();

    Config::set('session.driver', 'unsupported_driver');

    $this->artisan(DisableUserAccount::class, ['user' => $user->id])
        ->expectsOutputToContain('Session clearing not implemented for driver: unsupported_driver')
        ->expectsOutputToContain('User account has been disabled and all sessions cleared.')
        ->assertExitCode(0);

    $user->refresh();
    $this->assertNotNull($user->account_disabled_at);
});
