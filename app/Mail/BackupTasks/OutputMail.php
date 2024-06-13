<?php

namespace App\Mail\BackupTasks;

use App\Models\BackupTaskLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OutputMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public BackupTaskLog $backupTaskLog)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->backupTaskLog->successful_at ? 'Backup task completed' : 'Backup task failed',
        );
    }

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
