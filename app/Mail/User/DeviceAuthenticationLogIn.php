<?php

declare(strict_types=1);

namespace App\Mail\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class for notifying users about a new device login.
 *
 * This class constructs and sends an email to the user when a new login
 * to their account is detected from a mobile device, providing security
 * awareness and prompting them to review their account activity.
 */
class DeviceAuthenticationLogIn extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        /**
         * The user instance.
         */
        private readonly User $user
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Security Alert: New Device Login Detected'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.user.device-authentication-log-in',
            with: [
                'user' => $this->user,
            ]
        );
    }
}
