<?php

use App\Mail\SuspensionLiftedMail;
use App\Models\UserSuspension;

it('has the correct contents!', function () {
    $suspension = UserSuspension::factory()->create();
    $user = $suspension->user;

    $mail = new SuspensionLiftedMail($user, $suspension);

    $mail->assertHasSubject('Your suspension has been lifted');
    $mail->assertSeeInText('Your suspension has been lifted. You are now able to log in.');
    $mail->assertSeeInText('Please be aware that any future breaking of rules will result in your account being suspended once more.');
});
