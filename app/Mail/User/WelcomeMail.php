<?php

declare(strict_types=1);

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Auth\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Welcome to :app!', ['app' => config('app.name')])
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.user.welcome-mail',
            with: [
                'url' => route('remote-servers.create'),
                'creator' => 'Lewis - Creator of ' . config('app.name'),
            ],
        );
    }
}
