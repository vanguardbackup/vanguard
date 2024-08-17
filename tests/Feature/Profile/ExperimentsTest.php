<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

beforeEach(function (): void {
    $this->user = User::factory()->create(['password' => Hash::make('password')]);
    $this->actingAs($this->user);
});

test('the component can be rendered', function (): void {
    Volt::test('profile.experiments-manager')
        ->assertOk();
});

test('the page can be visited by authenticated users', function (): void {
    $this->get(route('profile.experiments'))
        ->assertOk()
        ->assertSeeLivewire('profile.experiments-manager');
});

test('the page cannot be visited by guests', function (): void {
    Auth::logout();
    $this->get(route('profile.experiments'))
        ->assertRedirect('login');
    $this->assertGuest();
});
