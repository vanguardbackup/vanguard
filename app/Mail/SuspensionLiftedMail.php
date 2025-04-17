<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuspensionLiftedMail extends Mailable
{
    use Queueable;
    use SerializesModels;
    public function __construct(public User $user, public UserSuspension $userSuspension)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your suspension has been lifted')
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.suspension-lifted-mail',
            with: [
                'suspension' => $this->userSuspension,
                'user' => $this->user,
            ],
        );
    }
}
