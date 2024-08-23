<?php

declare(strict_types=1);

use App\Models\UserConnection;

test('it returns true if the provider is github', function (): void {
    $userProvider = UserConnection::factory()->github()->create();

    $this->assertTrue($userProvider->isGithub());
});

test('it returns false if the provider is not github', function (): void {
    $userProvider = UserConnection::factory()->gitlab()->create();

    $this->assertTrue($userProvider->isGitLab());
});

test('it returns true if the provider is gitlab', function (): void {
    $userProvider = UserConnection::factory()->gitlab()->create();

    $this->assertTrue($userProvider->isGitLab());
});

test('it returns false if the provider is not gitlab', function (): void {
    $userProvider = UserConnection::factory()->github()->create();

    $this->assertFalse($userProvider->isGitLab());
});

test('it returns true if the provider is bitbucket', function (): void {
    $userProvider = UserConnection::factory()->bitbucket()->create();

    $this->assertTrue($userProvider->isBitbucket());
});

test('it returns false if the provider is not bitbucket', function (): void {
    $userProvider = UserConnection::factory()->github()->create();

    $this->assertFalse($userProvider->isBitbucket());
});
