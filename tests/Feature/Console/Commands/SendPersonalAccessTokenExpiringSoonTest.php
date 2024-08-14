<?php

declare(strict_types=1);

use App\Console\Commands\SendPersonalAccessTokenExpiringSoon;
use App\Mail\PersonalAccessTokenExpiringSoonMail;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
});

it('handles no tokens needing notification', function (): void {
    $this->artisan(SendPersonalAccessTokenExpiringSoon::class)
        ->expectsOutputToContain('No tokens requiring notifications.')
        ->assertExitCode(0);

    Mail::assertNothingQueued();
});

it('queues notifications for tokens needing notification', function (): void {
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);

    PersonalAccessToken::factory()->for($user1, 'tokenable')->create([
        'expires_at' => Carbon::now()->addDays(2),
        'last_notification_sent_at' => null,
    ]);
    PersonalAccessToken::factory()->for($user2, 'tokenable')->create([
        'expires_at' => Carbon::now()->addDays(1),
        'last_notification_sent_at' => Carbon::now()->subDays(2),
    ]);

    $this->artisan(SendPersonalAccessTokenExpiringSoon::class)
        ->expectsOutputToContain('Notifications queued for 2 expiring tokens.')
        ->assertExitCode(0);

    Mail::assertQueued(PersonalAccessTokenExpiringSoonMail::class, 2);
    Mail::assertQueued(PersonalAccessTokenExpiringSoonMail::class, function ($mail) use ($user1) {
        return $mail->hasTo($user1->email);
    });
    Mail::assertQueued(PersonalAccessTokenExpiringSoonMail::class, function ($mail) use ($user2) {
        return $mail->hasTo($user2->email);
    });
});

it('does not queue notifications for tokens not needing notification', function (): void {
    PersonalAccessToken::factory()->for(User::factory(), 'tokenable')->create([
        'expires_at' => Carbon::now()->addDays(4),
        'last_notification_sent_at' => null,
    ]);

    PersonalAccessToken::factory()->for(User::factory(), 'tokenable')->create([
        'expires_at' => Carbon::now()->addDays(2),
        'last_notification_sent_at' => Carbon::now()->subHours(12),
    ]);

    $this->artisan(SendPersonalAccessTokenExpiringSoon::class)
        ->expectsOutputToContain('No tokens requiring notifications.')
        ->assertExitCode(0);

    Mail::assertNothingQueued();
});

it('queues notifications only for tokens needing notification within 3 days', function (): void {
    $user = User::factory()->create();
    PersonalAccessToken::factory()->for($user, 'tokenable')->create([
        'expires_at' => Carbon::now()->addDays(2),
        'last_notification_sent_at' => null,
    ]);
    PersonalAccessToken::factory()->for($user, 'tokenable')->create([
        'expires_at' => Carbon::now()->addDays(3),
        'last_notification_sent_at' => Carbon::now()->subDays(2),
    ]);
    PersonalAccessToken::factory()->for($user, 'tokenable')->create([
        'expires_at' => Carbon::now()->addDays(4),
        'last_notification_sent_at' => null,
    ]);

    $this->artisan(SendPersonalAccessTokenExpiringSoon::class)
        ->expectsOutputToContain('Notifications queued for 2 expiring tokens.')
        ->assertExitCode(0);

    Mail::assertQueued(PersonalAccessTokenExpiringSoonMail::class, 2);
});

it('handles a mix of tokens needing and not needing notification', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    PersonalAccessToken::factory()->for($user1, 'tokenable')->create([
        'expires_at' => Carbon::now()->addDays(2),
        'last_notification_sent_at' => null,
    ]);
    PersonalAccessToken::factory()->for($user2, 'tokenable')->create([
        'expires_at' => Carbon::now()->addDays(2),
        'last_notification_sent_at' => Carbon::now()->subHours(12),
    ]);

    $this->artisan(SendPersonalAccessTokenExpiringSoon::class)
        ->expectsOutputToContain('Notifications queued for 1 expiring tokens.')
        ->assertExitCode(0);

    Mail::assertQueued(PersonalAccessTokenExpiringSoonMail::class, 1);
    Mail::assertQueued(PersonalAccessTokenExpiringSoonMail::class, function ($mail) use ($user1) {
        return $mail->hasTo($user1->email);
    });
});

it('does not consider already expired tokens', function (): void {
    $user = User::factory()->create();
    PersonalAccessToken::factory()->for($user, 'tokenable')->create([
        'expires_at' => Carbon::now()->subDay(),
        'last_notification_sent_at' => null,
    ]);

    $this->artisan(SendPersonalAccessTokenExpiringSoon::class)
        ->expectsOutputToContain('No tokens requiring notifications.')
        ->assertExitCode(0);

    Mail::assertNothingQueued();
});

it('updates last_notification_sent_at after queueing a notification', function (): void {
    $user = User::factory()->create();
    $token = PersonalAccessToken::factory()->for($user, 'tokenable')->create([
        'expires_at' => now()->addDays(2),
        'last_notification_sent_at' => null,
    ]);

    $this->artisan(SendPersonalAccessTokenExpiringSoon::class)
        ->expectsOutputToContain('Notifications queued for 1 expiring tokens.')
        ->assertExitCode(0);

    Mail::assertQueued(PersonalAccessTokenExpiringSoonMail::class, 1);

    $token->refresh();

    expect($token->last_notification_sent_at)->not->toBeNull();

    $lastNotificationSent = $token->last_notification_sent_at instanceof Carbon
        ? $token->last_notification_sent_at
        : Carbon::parse($token->last_notification_sent_at);

    expect($lastNotificationSent)->toBeInstanceOf(Carbon::class)
        ->and($lastNotificationSent->timestamp)->toBeGreaterThan(now()->subMinute()->timestamp)
        ->and($lastNotificationSent->timestamp)->toBeLessThanOrEqual(now()->timestamp);
});
