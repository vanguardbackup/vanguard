<?php

namespace App\Mail;

use App\Models\BackupDestination;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupConnectionFailure extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly BackupDestination $backupDestination, public readonly User $user, public readonly string $errorMessage)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Error connecting to backup destination'),
        );
    }

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
