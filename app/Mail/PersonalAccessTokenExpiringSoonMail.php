<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Mailable for notifying users about their expiring personal access token.
 */
class PersonalAccessTokenExpiringSoonMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;
    /**
     * The user associated with the token.
     */
    private readonly User $user;

    public function __construct(private readonly PersonalAccessToken $personalAccessToken)
    {
        $this->user = $this->personalAccessToken->getAttribute('tokenable');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Action Required: Your API Token '{$this->personalAccessToken->getAttribute('name')}' is Expiring Soon",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.personal-access-token-expiring-soon',
            with: [
                'userName' => $this->user->getAttribute('first_name'),
                'tokenName' => $this->personalAccessToken->getAttribute('name'),
                'createdAt' => $this->formatDate($this->personalAccessToken->getAttribute('created_at')),
                'lastUsedAt' => $this->formatDate($this->personalAccessToken->getAttribute('last_used_at'), 'Never used'),
                'expiresAt' => $this->formatDate($this->personalAccessToken->getAttribute('expires_at')),
                'daysUntilExpiration' => now()->diffInDays($this->personalAccessToken->getAttribute('expires_at')),
                'manageTokensUrl' => URL::route('profile.api'),
            ],
        );
    }

    /**
     * Format a date nicely or return a default value if null.
     */
    private function formatDate(?Carbon $carbon, string $default = 'N/A'): string
    {
        return $carbon instanceof Carbon
            ? $carbon->format('F j, Y \a\t g:i A')
            : $default;
    }
}
