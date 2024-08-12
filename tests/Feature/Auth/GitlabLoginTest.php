<?php

declare(strict_types=1);

use App\Mail\User\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Masmerise\Toaster\Toaster;
use Mockery\MockInterface;

it('redirects to GitLab when client ID and secret are set', function (): void {
    config()->set('services.gitlab.client_id', 'fake-client-id');
    config()->set('services.gitlab.client_secret', 'fake-client-secret');

    $response = $this->get(route('gitlab.redirect'));

    $response->assertRedirect();
});

it('redirects back to login with error if GitLab login is not enabled', function (): void {
    config()->set('services.gitlab.client_id', null);
    config()->set('services.gitlab.client_secret', null);

    $response = $this->get(route('gitlab.redirect'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('loginError', 'GitLab login is not enabled.');
});

it('logs in existing user', function (): void {
    Toaster::fake();
    $user = User::factory()->create();

    /**
     * @var \Laravel\Socialite\Contracts\User|MockInterface
     */
    $mockGitlabUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $mockGitlabUser->shouldReceive('getEmail')->andReturn($user->email);

    Socialite::shouldReceive('driver->user')->andReturn($mockGitlabUser);
    $this->get(route('gitlab.callback'))
        ->assertRedirect(route('overview'));

    Toaster::assertDispatched(__('Successfully logged in via GitLab!'));

    $this->assertAuthenticatedAs($user);
});

it('creates a new user if none exists', function (): void {
    Toaster::fake();
    Mail::fake();
    $this->assertDatabaseMissing('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
    ]);
    /**
     * @var \Laravel\Socialite\Contracts\User|MockInterface
     */
    $mockGitlabUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $mockGitlabUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
    $mockGitlabUser->shouldReceive('getName')->andReturn('New User');

    Socialite::shouldReceive('driver->user')->andReturn($mockGitlabUser);

    $this->get(route('gitlab.callback'))
        ->assertRedirect(route('overview'));

    Toaster::assertDispatched(__('Successfully logged in via GitLab!'));

    $user = User::where('email', 'newuser@example.com')->first();
    $this->assertAuthenticatedAs($user);
    Mail::assertQueued(WelcomeMail::class);
    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
    ]);
});

it('redirects back to login with error if an exception is thrown', function (): void {
    Socialite::shouldReceive('driver->user')->andThrow(new InvalidStateException('Invalid State'));

    $response = $this->get(route('gitlab.callback'))
        ->assertRedirect(route('login'));
    $response->assertSessionHas('loginError', 'Authentication failed. There may be an error with GitLab. Please try again later.');
});
