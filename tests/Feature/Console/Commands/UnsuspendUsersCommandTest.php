<?php

declare(strict_types=1);

use App\Console\Commands\UnsuspendUsersCommand;
use App\Jobs\LiftSuspensionsOnUserJob;
use App\Models\UserSuspension;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

test('unsuspends eligible users', function (): void {
    Queue::fake();

    $suspendToday = UserSuspension::factory()->create([
        'suspended_until' => Carbon::today(),
        'lifted_at' => null,
    ]);

    $suspendYesterday = UserSuspension::factory()->create([
        'suspended_until' => Carbon::yesterday(),
        'lifted_at' => null,
    ]);

    $suspendLastWeek = UserSuspension::factory()->create([
        'suspended_until' => Carbon::now()->subWeek(),
        'lifted_at' => null,
    ]);

    $suspendEarlierToday = UserSuspension::factory()->create([
        'suspended_until' => Carbon::now()->subHours(3),
        'lifted_at' => null,
    ]);

    $suspendTomorrow = UserSuspension::factory()->create([
        'suspended_until' => Carbon::tomorrow(),
        'lifted_at' => null,
    ]);

    $suspendNextWeek = UserSuspension::factory()->create([
        'suspended_until' => Carbon::now()->addWeek(),
        'lifted_at' => null,
    ]);

    $suspendPermanent = UserSuspension::factory()->permanent()->create([
        'lifted_at' => null,
    ]);

    $liftedYesterday = UserSuspension::factory()->create([
        'suspended_until' => Carbon::yesterday(),
        'lifted_at' => Carbon::now()->subDay(),
    ]);

    $liftedPermanent = UserSuspension::factory()->permanent()->create([
        'lifted_at' => Carbon::now()->subDays(2),
    ]);

    $this->artisan(UnsuspendUsersCommand::class)
        ->expectsOutputToContain('Found 4 suspensions to lift.')
        ->expectsOutputToContain('Dispatched jobs to lift suspensions.');

    Queue::assertPushed(LiftSuspensionsOnUserJob::class, 4);
    Queue::assertPushed(LiftSuspensionsOnUserJob::class, function ($job) use ($suspendToday): bool {
        return $job->suspensionId === $suspendToday->id;
    });
    Queue::assertPushed(LiftSuspensionsOnUserJob::class, function ($job) use ($suspendYesterday): bool {
        return $job->suspensionId === $suspendYesterday->id;
    });
    Queue::assertPushed(LiftSuspensionsOnUserJob::class, function ($job) use ($suspendLastWeek): bool {
        return $job->suspensionId === $suspendLastWeek->id;
    });
    Queue::assertPushed(LiftSuspensionsOnUserJob::class, function ($job) use ($suspendEarlierToday): bool {
        return $job->suspensionId === $suspendEarlierToday->id;
    });
});

test('handles empty results gracefully', function (): void {
    Queue::fake();

    UserSuspension::factory()->create([
        'suspended_until' => Carbon::tomorrow(),
        'lifted_at' => null,
    ]);

    UserSuspension::factory()->permanent()->create([
        'lifted_at' => null,
    ]);

    UserSuspension::factory()->create([
        'suspended_until' => Carbon::yesterday(),
        'lifted_at' => Carbon::now()->subHour(),
    ]);

    $this->artisan(UnsuspendUsersCommand::class)
        ->expectsOutputToContain('Found 0 suspensions to lift.');

    Queue::assertNotPushed(LiftSuspensionsOnUserJob::class);
});

test('processes edge case dates correctly', function (): void {
    Queue::fake();

    $midnightSuspension = UserSuspension::factory()->create([
        'suspended_until' => Carbon::today()->startOfDay(),
        'lifted_at' => null,
    ]);

    $endOfDaySuspension = UserSuspension::factory()->create([
        'suspended_until' => Carbon::today()->endOfDay(),
        'lifted_at' => null,
    ]);

    $this->artisan(UnsuspendUsersCommand::class)
        ->expectsOutputToContain('Found 2 suspensions to lift.')
        ->expectsOutputToContain('Dispatched jobs to lift suspensions.');

    Queue::assertPushed(LiftSuspensionsOnUserJob::class, 2);
    Queue::assertPushed(LiftSuspensionsOnUserJob::class, function ($job) use ($midnightSuspension): bool {
        return $job->suspensionId === $midnightSuspension->id;
    });
    Queue::assertPushed(LiftSuspensionsOnUserJob::class, function ($job) use ($endOfDaySuspension): bool {
        return $job->suspensionId === $endOfDaySuspension->id;
    });
});
