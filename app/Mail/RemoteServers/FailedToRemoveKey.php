<?php

declare(strict_types=1);

namespace App\Mail\RemoteServers;

use App\Models\RemoteServer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class for sending notifications when SSH key removal fails.
 *
 * This class is responsible for constructing and sending an email
 * when the system fails to remove an SSH key from a remote server.
 */
class FailedToRemoveKey extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly RemoteServer $remoteServer, public readonly string $message = '')
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Failed to Remove SSH Key'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.remote-servers.failed-to-remove-key',
            with: [
                'remoteServer' => $this->remoteServer,
                'message' => $this->message,
                'user' => $this->remoteServer->getAttribute('user'),
            ],
        );
    }
}
