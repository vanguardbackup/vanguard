<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    // Enable the device authentication endpoint by default for most tests
    Config::set('app.enable_device_authentication_endpoint', true);
});

test('authenticates user and returns a token when endpoint is enabled', function (): void {
    $response = $this->postJson('api/sanctum/token', [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'test_device',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token']);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

test('returns 404 when device authentication endpoint is disabled', function (): void {
    Config::set('app.enable_device_authentication_endpoint', false);

    $response = $this->postJson('api/sanctum/token', [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'test_device',
    ]);

    $response->assertStatus(404);
});

test('returns validation error for missing fields', function (): void {
    $response = $this->postJson('api/sanctum/token', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});

test('returns validation error for invalid email', function (): void {
    $response = $this->postJson('api/sanctum/token', [
        'email' => 'invalid-email',
        'password' => 'password',
        'device_name' => 'test_device',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('returns error for incorrect credentials', function (): void {
    $response = $this->postJson('api/sanctum/token', [
        'email' => 'test@example.com',
        'password' => 'wrong_password',
        'device_name' => 'test_device',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('returns error for non-existent user', function (): void {
    $response = $this->postJson('api/sanctum/token', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
        'device_name' => 'test_device',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('creates a new token for an already authenticated user', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('api/sanctum/token', [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'another_device',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token']);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});
