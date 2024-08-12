<?php

declare(strict_types=1);

use App\Models\User;

it('returns true if the token is a mobile token', function (): void {
    $user = User::factory()->create();

    $user->createMobileToken('My Mobile Token');

    $token = $user->tokens()->first();

    $this->assertTrue($token->isMobileToken());
});

it('returns false if the token is not a mobile token', function (): void {
    $user = User::factory()->create();

    $user->createToken('My Regular Token');

    $token = $user->tokens()->first();

    $this->assertFalse($token->isMobileToken());
});
