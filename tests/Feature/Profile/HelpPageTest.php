<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->user = User::factory()->create(['password' => Hash::make('password')]);
    $this->actingAs($this->user);
});

test('the page can be visited by authenticated users', function (): void {
    $this->get(route('profile.help'))
        ->assertOk();
});

test('the page cannot be visited by guests', function (): void {
    Auth::logout();
    $this->get(route('profile.help'))
        ->assertRedirect('login');
    $this->assertGuest();
});
