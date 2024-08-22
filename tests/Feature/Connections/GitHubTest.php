<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function (): void {
    $this->socialiteMock = Mockery::mock('alias:' . Socialite::class);
});

afterEach(function (): void {
    Mockery::close();
});

it('redirects to GitHub', function (): void {
    $this->socialiteMock->shouldReceive('driver->redirect')
        ->once()
        ->andReturn(redirect('https://github.com/login'));

    $response = $this->get(route('github.redirect'));

    $response->assertRedirect('https://github.com/login');
});

it('handles GitHub callback for new user', function (): void {
    $mock = Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive([
        'getId' => '123456',
        'getEmail' => 'test@example.com',
        'getName' => 'Test User',
        'token' => 'mock-access-token',
        'refreshToken' => 'mock-refresh-token',
    ]);

    $this->socialiteMock->shouldReceive('driver->user')
        ->once()
        ->andReturn($mock);

    $response = $this->get(route('github.callback'));

    $response->assertRedirect(route('overview'));
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    $this->assertDatabaseHas('user_connections', [
        'provider_name' => UserConnection::PROVIDER_GITHUB,
        'provider_user_id' => '123456',
    ]);
    expect(Auth::check())->toBeTrue();
});

it('handles GitHub callback for existing user', function (): void {
    $user = User::factory()->create(['email' => 'existing@example.com']);

    $mock = Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive([
        'getId' => '789012',
        'getEmail' => 'existing@example.com',
        'getName' => 'Existing User',
        'token' => 'mock-access-token',
        'refreshToken' => 'mock-refresh-token',
    ]);

    $this->socialiteMock->shouldReceive('driver->user')
        ->once()
        ->andReturn($mock);

    $response = $this->get(route('github.callback'));

    $response->assertRedirect(route('overview'));
    $this->assertDatabaseHas('user_connections', [
        'user_id' => $user->id,
        'provider_name' => UserConnection::PROVIDER_GITHUB,
        'provider_user_id' => '789012',
    ]);
    expect(Auth::id())->toBe($user->id);
});

it('handles GitHub callback for already linked account', function (): void {
    $user = User::factory()->create();
    UserConnection::factory()->create([
        'user_id' => $user->id,
        'provider_name' => UserConnection::PROVIDER_GITHUB,
        'provider_user_id' => '123456',
    ]);

    $mock = Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive([
        'getId' => '123456',
        'getEmail' => $user->email,
    ]);

    $this->socialiteMock->shouldReceive('driver->user')
        ->once()
        ->andReturn($mock);

    $response = $this->get(route('github.callback'));

    $response->assertRedirect(route('overview'));
    expect(Auth::id())->toBe($user->id);
});
