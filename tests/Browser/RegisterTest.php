<?php

use Laravel\Dusk\Browser;

test('a user can register', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit(route('register'))
            ->type('@name', 'John Doe')
            ->type('@email', 'user@email.com')
            ->type('@password', 'password123')
            ->type('@password_confirmation', 'password123')
            ->press('@create_account_button')
            ->waitForLocation(route('overview'))
            ->assertPathIs('/overview');
    });
});
