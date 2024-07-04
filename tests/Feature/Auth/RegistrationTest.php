<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Mail\User\WelcomeMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function (): void {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('new users can register', function (): void {
    Mail::fake();

    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('overview', absolute: false));

    $this->assertAuthenticated();

    Mail::assertQueued(WelcomeMail::class, function ($mail) {
        return $mail->hasTo('test@example.com');
    });
});
