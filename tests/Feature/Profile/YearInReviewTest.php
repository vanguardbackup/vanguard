<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->user = User::factory()->create(['password' => Hash::make('password')]);
    $this->actingAs($this->user);
});

test('the page cannot be visited if the year in review feature is not enabled', function (): void {
    Config::set('app.year_in_review.enabled', false);

    $this->get(route('profile.year-in-review'))
        ->assertStatus(302);
});

test('the page can be visited by authenticated users', function (): void {
    Config::set('app.year_in_review.enabled', true);
    Carbon::setTestNow(Carbon::create(now()->year, 12, 15));

    $this->get(route('profile.year-in-review'))
        ->assertOk();
});

test('the page cannot be visited by guests', function (): void {
    Auth::logout();
    $this->get(route('profile.year-in-review'))
        ->assertRedirect('login');
    $this->assertGuest();
});
