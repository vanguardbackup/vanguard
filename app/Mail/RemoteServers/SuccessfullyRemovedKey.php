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

class SuccessfullyRemovedKey extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly RemoteServer $remoteServer)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Notice of SSH Key Removal'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.remote-servers.successfully-removed-key',
            with: ['remoteServer' => $this->remoteServer, 'user' => $this->remoteServer->getAttribute('user')],
        );
    }
}
