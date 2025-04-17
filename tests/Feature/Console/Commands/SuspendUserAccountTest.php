<?php

declare(strict_types=1);

use App\Console\Commands\SuspendUserAccount;
use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Exception\RuntimeException;

test('a user ID is required', function (): void {
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Not enough arguments (missing: "user").');

    $this->artisan(SuspendUserAccount::class);
});

test('the entered value must be an integer', function (): void {
    $this->artisan(SuspendUserAccount::class, ['user' => 'not-an-integer'])
        ->expectsOutputToContain('The value provided is not an id.')
        ->assertExitCode(0);
});

test('a user must exist', function (): void {
    $this->artisan(SuspendUserAccount::class, ['user' => 999])
        ->expectsOutputToContain('User with ID 999 not found.')
        ->assertExitCode(0);
});

test('an admin account cannot be disabled', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $user = User::factory()->create(['email' => 'admin@email.com']);

    $this->artisan(SuspendUserAccount::class, ['user' => $user->id])
        ->expectsOutputToContain('Cannot suspend an administrator account.')
        ->assertExitCode(0);

    $this->assertFalse($user->hasSuspendedAccount());
});

test('it returns success if the account is already disabled', function (): void {
    $suspension = UserSuspension::factory()->create();
    $user = $suspension->user;

    $this->artisan(SuspendUserAccount::class, ['user' => $user->id])
        ->expectsOutputToContain('User account is already suspended.')
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

    $this->artisan(SuspendUserAccount::class, ['user' => $user->id])
        ->expectsOutputToContain('User account has been suspended and all sessions cleared.')
        ->assertExitCode(0);

    $user->refresh();
    $this->assertTrue($user->hasSuspendedAccount());
});
