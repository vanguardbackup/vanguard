<?php

declare(strict_types=1);

namespace App\Livewire\NotificationStreams\Forms\Traits;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

trait LogsJsErrors
{
    /**
     * Listen for js error events from frontend and log them.
     */
    #[On('jsError')]
    public function logJsError(string $message): void
    {
        Log::error('Error from js script for Telegram authentication.', ['error' => $message]);
    }
}
