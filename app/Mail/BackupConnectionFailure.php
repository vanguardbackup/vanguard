<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\BackupDestination;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class for sending emails when a backup destination connection fails.
 *
 * This class is responsible for constructing and sending an email
 * notification to a user when there's an error connecting to a backup destination.
 */
class BackupConnectionFailure extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly BackupDestination $backupDestination, public readonly User $user, public readonly string $errorMessage)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Error connecting to backup destination'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.backup-connection-failure',
            with: [
                'backupDestination' => $this->backupDestination,
                'user' => $this->user,
                'errorMessage' => $this->errorMessage,
                'url' => route('backup-destinations.edit', $this->backupDestination),
            ],
        );
    }
}
