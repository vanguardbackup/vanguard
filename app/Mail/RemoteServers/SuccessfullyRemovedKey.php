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
 * Mailable class for sending notifications when an SSH key is successfully removed.
 *
 * This class is responsible for constructing and sending an email
 * when the system successfully removes an SSH key from a remote server.
 */
class SuccessfullyRemovedKey extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly RemoteServer $remoteServer)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Notice of SSH Key Removal'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.remote-servers.successfully-removed-key',
            with: [
                'remoteServer' => $this->remoteServer,
                'user' => $this->remoteServer->getAttribute('user'),
            ],
        );
    }
}
