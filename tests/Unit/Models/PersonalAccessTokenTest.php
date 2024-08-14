<?php

declare(strict_types=1);

use App\Models\PersonalAccessToken;
use App\Models\User;
use Carbon\Carbon;

it('returns true if the token is a mobile token', function (): void {
    $user = User::factory()->create();

    $user->createMobileToken('My Mobile Token');

    $token = $user->tokens()->first();

    expect($token->isMobileToken())->toBeTrue();
});

it('returns false if the token is not a mobile token', function (): void {
    $user = User::factory()->create();

    $user->createToken('My Regular Token');

    $token = $user->tokens()->first();

    expect($token->isMobileToken())->toBeFalse();
});

it('scopes non-expired tokens expiring within three days', function (): void {
    $user = User::factory()->create();

    // Create tokens with different expiration dates
    $user->createToken('Expires in 1 day', ['*'], now()->addDay());
    $user->createToken('Expires in 2 days', ['*'], now()->addDays(2));
    $user->createToken('Expires in 3 days', ['*'], now()->addDays(3));
    $user->createToken('Expires in 4 days', ['*'], now()->addDays(4));
    $user->createToken('Expired 1 hour ago', ['*'], now()->subHour());

    $expiringTokens = PersonalAccessToken::expiringWithinThreeDays()->get();

    expect($expiringTokens)->toHaveCount(3)
        ->and($expiringTokens->pluck('name'))->toContain('Expires in 1 day', 'Expires in 2 days', 'Expires in 3 days')
        ->and($expiringTokens->pluck('name'))->not->toContain('Expires in 4 days', 'Expired 1 hour ago');
});

it('scopes tokens expiring within three days', function (): void {
    PersonalAccessToken::factory()->expiringSoon()->create();
    PersonalAccessToken::factory()->create([
        'expires_at' => Carbon::now()->addDays(4),
    ]);
    PersonalAccessToken::factory()->expired()->create();

    $tokens = PersonalAccessToken::expiringWithinThreeDays()->get();

    expect($tokens)->toHaveCount(1);
});

it('scopes tokens needing notification', function (): void {
    $now = Carbon::now();

    PersonalAccessToken::factory()->expiringSoon()->neverNotified()->create();
    PersonalAccessToken::factory()->expiringSoon()->notifiedLongAgo()->create();
    PersonalAccessToken::factory()->expiringSoon()->recentlyNotified()->create();
    PersonalAccessToken::factory()->create([
        'expires_at' => $now->copy()->addDays(4),
    ]);
    PersonalAccessToken::factory()->expired()->create();

    $tokens = PersonalAccessToken::needingNotification()->get();

    expect($tokens)->toHaveCount(2);
});
