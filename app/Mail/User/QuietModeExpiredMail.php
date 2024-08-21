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
 * Class QuietModeExpiredMail
 *
 * This mailable is responsible for sending an email notification to users
 * when their Quiet Mode period has expired and been automatically deactivated.
 */
class QuietModeExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly User $user)
    {
        //
    }

    /**
     * Get the message envelope.
     *
     * This method defines the subject line and any additional headers for the email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Welcome back! Your Quiet Mode period has ended'),
        );
    }

    /**
     * Get the message content definition.
     *
     * This method specifies the view to be used for the email content
     * and any data that should be passed to that view.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.user.quiet-mode-expired-mail',
            with: [
                'first_name' => $this->user->getAttribute('first_name'),
            ],
        );
    }
}
