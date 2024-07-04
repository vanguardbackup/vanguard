<?php

declare(strict_types=1);

use App\Mail\User\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('the contents of the welcome mail are correct', function () {

    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);

    $expectedUrl = route('remote-servers.create');

    $mail = new WelcomeMail($user);
    Mail::to($user->email)->send($mail);

    $app = config('app.name');

    $mail->assertHasSubject(__('Welcome to :app!', ['app' => $app]));
    $mail->assertSeeInHtml('Welcome to ' . $app . '!');
    $mail->assertSeeInHtml($user->first_name);
    $mail->assertSeeInHtml('Thank you so much for creating an account on ' . $app . '. We are thrilled to have you on board!');
    $mail->assertSeeInHtml($app . ' was born out of a desire to create a simple, no-cost solution for backing up files and databases from servers.');
    $mail->assertSeeInHtml('If you ever have any questions or feedback, please don’t hesitate to reach out. We’d love to hear from you.');
    $mail->assertSeeInHtml('To get started, click the button below to link your first remote server to ' . $app . '.');
    $mail->assertSeeInHtml('Add Remote Server');
    $mail->assertSeeInHtml($expectedUrl);
    $mail->assertSeeInHtml('Thanks again,');
    $mail->assertSeeInHtml('Lewis - Creator of ' . $app);
});
