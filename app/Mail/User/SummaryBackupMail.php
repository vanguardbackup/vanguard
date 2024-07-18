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

class SummaryBackupMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly array $data, private readonly User $user)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your Backup Performance Recap for :dateRange', [
                'dateRange' => $this->getDateRangeString(),
            ])
        );
    }

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

    private function getDateRangeString(): string
    {
        $startDate = Carbon::parse($this->data['date_range']['start']);
        $endDate = Carbon::parse($this->data['date_range']['end']);

        return $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
    }
}
