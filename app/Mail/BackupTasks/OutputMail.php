<?php

declare(strict_types=1);

namespace App\Mail\BackupTasks;

use App\Models\BackupTaskLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class for sending backup task output notifications.
 *
 * This class is responsible for constructing and sending an email
 * with the output of a backup task, indicating whether it was successful or failed.
 */
class OutputMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public BackupTaskLog $backupTaskLog)
    {
        //
    }

    /**
     * Get the message envelope.
     *
     * The subject of the email is determined by the success status of the backup task.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->backupTaskLog->getAttribute('successful_at') ? 'Backup task completed' : 'Backup task failed',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.backup-tasks.output-mail',
            with: [
                'backupTaskLog' => $this->backupTaskLog,
            ],
        );
    }
}
