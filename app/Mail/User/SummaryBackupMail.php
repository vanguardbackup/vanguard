<?php

declare(strict_types=1);

namespace App\Mail\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class for sending backup performance summary emails to users.
 *
 * This class is responsible for constructing and sending an email
 * containing a recap of the user's backup performance over a specified date range.
 */
class SummaryBackupMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly array $data, private readonly User $user)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your Backup Performance Recap for :dateRange', [
                'dateRange' => $this->getDateRangeString(),
            ])
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.user.summary-backup-mail',
            with: [
                'user' => $this->user,
                'data' => $this->data,
            ]
        );
    }

    /**
     * Get a formatted string representation of the date range.
     *
     * @return string The formatted date range string (e.g., "Aug 01 - Aug 07, 2023")
     */
    private function getDateRangeString(): string
    {
        $startDate = Carbon::parse($this->data['date_range']['start']);
        $endDate = Carbon::parse($this->data['date_range']['end']);

        return $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
    }
}
