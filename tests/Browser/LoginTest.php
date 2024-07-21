<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;

test('a user can login', function (): void {
    $password = 'testpassword123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    $this->browse(function (Browser $browser) use ($user, $password): void {
        $browser->visit(route('login'))
            ->type('@email', $user->getAttribute('email'))
            ->type('@password', $password)
            ->press('@login-button')
            ->waitForLocation(route('overview'))
            ->assertPathIs('/overview');
    });
});
