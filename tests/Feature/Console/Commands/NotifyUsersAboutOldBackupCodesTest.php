<?php

declare(strict_types=1);

use App\Console\Commands\NotifyUsersAboutOldBackupCodes;
use App\Mail\User\TwoFactor\LongstandingTwoFactorFollowUpMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
});

test('it notifies users with old backup codes', function (): void {
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);

    $user1->createTwoFactorAuth();
    $user1->enableTwoFactorAuth();
    $user1->twoFactorAuth->recovery_codes_generated_at = now()->subYear()->subDay();
    $user1->twoFactorAuth->save();

    $user2->createTwoFactorAuth();
    $user2->enableTwoFactorAuth();
    $user2->twoFactorAuth->recovery_codes_generated_at = now()->subYear()->subDay();
    $user2->twoFactorAuth->save();

    $this->artisan(NotifyUsersAboutOldBackupCodes::class)
        ->assertExitCode(0);

    Mail::assertQueued(LongstandingTwoFactorFollowUpMail::class, 2);
});

test('it does not send emails when no users have old backup codes', function (): void {
    $user = User::factory()->create();
    $user->createTwoFactorAuth();
    $user->enableTwoFactorAuth();
    $user->twoFactorAuth->recovery_codes_generated_at = now()->subMonths(6);
    $user->twoFactorAuth->save();

    $this->artisan(NotifyUsersAboutOldBackupCodes::class)
        ->doesntExpectOutput('Sent')
        ->assertExitCode(0);

    Mail::assertNothingQueued();
});

test('it handles users without two-factor authentication', function (): void {
    User::factory()->create();

    $this->artisan(NotifyUsersAboutOldBackupCodes::class)
        ->doesntExpectOutput('Sent')
        ->assertExitCode(0);

    Mail::assertNothingQueued();
});

test('it correctly identifies users with exactly one-year-old backup codes', function (): void {
    $user = User::factory()->create();
    $user->createTwoFactorAuth();
    $user->enableTwoFactorAuth();
    $user->twoFactorAuth->recovery_codes_generated_at = now()->subYear();
    $user->twoFactorAuth->save();

    $this->artisan(NotifyUsersAboutOldBackupCodes::class)
        ->doesntExpectOutput('Sent')
        ->assertExitCode(0);

    Mail::assertNothingQueued();
});
