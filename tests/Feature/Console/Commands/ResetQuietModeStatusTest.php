<?php

declare(strict_types=1);

use App\Console\Commands\ResetQuietModeStatus;
use App\Mail\User\QuietModeExpiredMail;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    Mail::fake();
    Carbon::setTestNow('2023-08-21 12:00:00');
});

test('it resets quiet mode for users with expired quiet mode', function (): void {
    $user1 = User::factory()->create(['quiet_until' => Carbon::yesterday()->setTime(0, 0)]);
    $user2 = User::factory()->create(['quiet_until' => Carbon::today()->setTime(0, 0)]);
    $user3 = User::factory()->create(['quiet_until' => Carbon::tomorrow()->setTime(0, 0)]);

    $this->artisan(ResetQuietModeStatus::class)
        ->assertExitCode(0);

    $user1->refresh();
    $user2->refresh();
    $user3->refresh();

    expect($user1->hasQuietMode())->toBeFalse()
        ->and($user2->hasQuietMode())->toBeFalse()
        ->and($user3->hasQuietMode())->toBeTrue();

    Mail::assertQueued(QuietModeExpiredMail::class);
});

test('it does not reset quiet mode for users with active quiet mode', function (): void {
    $user = User::factory()->create(['quiet_until' => Carbon::tomorrow()->setTime(0, 0)]);

    $this->artisan(ResetQuietModeStatus::class)
        ->assertExitCode(0);

    $user->refresh();

    expect($user->hasQuietMode())->toBeTrue();
    Mail::assertNotQueued(QuietModeExpiredMail::class);
});

test('it handles users without quiet mode', function (): void {
    $user = User::factory()->create(['quiet_until' => null]);

    $this->artisan(ResetQuietModeStatus::class)
        ->assertExitCode(0);

    $user->refresh();

    expect($user->hasQuietMode())->toBeFalse();
    Mail::assertNotQueued(QuietModeExpiredMail::class);
});

test('it correctly identifies users with quiet mode expiring today', function (): void {
    $user = User::factory()->create(['quiet_until' => Carbon::today()->setTime(23, 59, 59)]);

    $this->artisan(ResetQuietModeStatus::class)
        ->assertExitCode(0);

    $user->refresh();

    expect($user->hasQuietMode())->toBeFalse();
});

test('it resets quiet mode for multiple users', function (): void {
    $expiredUsers = User::factory()->count(3)->create(['quiet_until' => Carbon::yesterday()->setTime(0, 0)]);
    $activeUsers = User::factory()->count(2)->create(['quiet_until' => Carbon::tomorrow()->setTime(0, 0)]);

    $this->artisan(ResetQuietModeStatus::class)
        ->assertExitCode(0);

    $expiredUsers->each(function ($user): void {
        $user->refresh();
        expect($user->hasQuietMode())->toBeFalse();
    });

    $activeUsers->each(function ($user): void {
        $user->refresh();
        expect($user->hasQuietMode())->toBeTrue();
    });

    Mail::assertQueued(QuietModeExpiredMail::class);
});

test('it outputs the correct number of reset users', function (): void {
    User::factory()->count(3)->create(['quiet_until' => Carbon::yesterday()->setTime(0, 0)]);
    User::factory()->count(2)->create(['quiet_until' => Carbon::tomorrow()->setTime(0, 0)]);

    $this->artisan(ResetQuietModeStatus::class)
        ->expectsOutputToContain('Quiet mode reset for 3 users.')
        ->assertExitCode(0);
});
