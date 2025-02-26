<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Str;

/**
 * Mailable class for sending emails when a backup task fails.
 *
 * This class is responsible for constructing and sending an email
 * notification to a user when a backup task encounters an error.
 */
class BackupTaskFailed extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $user, public string $taskName, public string $errorMessage)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Backup task failed'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.backup-task-failed',
            with: [
                'user' => $this->user,
                'taskName' => $this->taskName,
                'errorMessage' => Str::limit($this->errorMessage, 50),
            ],
        );
    }
}
