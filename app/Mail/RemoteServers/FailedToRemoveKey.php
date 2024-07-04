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

class FailedToRemoveKey extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly RemoteServer $remoteServer, public readonly string $message = '')
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Failed to Remove SSH Key'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.remote-servers.failed-to-remove-key',
            with: ['remoteServer' => $this->remoteServer, 'message' => $this->message, 'user' => $this->remoteServer->getAttribute('user')],
        );
    }
}
