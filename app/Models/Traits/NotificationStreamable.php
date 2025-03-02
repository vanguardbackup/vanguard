<?php

declare(strict_types=1);

// This class will contain all the Notification Stream triggers for the BackupTask model
// We will refactor this further in the future but this should help greatly with maintainability
// wasn't a fan of digging into a rather long model file for these!

namespace App\Models\Traits;

use App\Jobs\BackupTasks\SendDiscordNotificationJob;
use App\Jobs\BackupTasks\SendPushoverNotificationJob;
use App\Jobs\BackupTasks\SendSlackNotificationJob;
use App\Jobs\BackupTasks\SendTeamsNotificationJob;
use App\Jobs\BackupTasks\SendTelegramNotificationJob;
use App\Mail\BackupTasks\OutputMail;
use App\Models\BackupTaskLog;
use App\Models\NotificationStream;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use RuntimeException;

trait NotificationStreamable
{
    public function sendNotifications(): void
    {
        $jobQueue = 'backup-task-notifications';

        /** @var BackupTaskLog|null $latestLog */
        $latestLog = $this->fresh()?->logs()->latest()->first();

        if (! $latestLog) {
            return;
        }

        $backupTaskSuccessful = $latestLog->getAttribute('successful_at') !== null;

        /** @var Collection<int, NotificationStream> $notificationStreams */
        $notificationStreams = $this->notificationStreams;

        foreach ($notificationStreams as $notificationStream) {
            if (! $this->shouldSendNotification($notificationStream, $backupTaskSuccessful)) {
                continue;
            }

            $this->dispatchNotification($notificationStream, $latestLog, $jobQueue);
        }
    }

    /**
     * Determine if a notification should be sent based on the stream settings and backup outcome.
     */
    public function shouldSendNotification(NotificationStream $notificationStream, bool $backupTaskSuccessful): bool
    {
        if ($notificationStream->getAttribute('user') && $notificationStream->getAttribute('user')->hasQuietMode()) {
            return false;
        }

        if ($backupTaskSuccessful && $notificationStream->hasSuccessfulBackupNotificationsEnabled()) {
            return true;
        }

        return ! $backupTaskSuccessful && $notificationStream->hasFailedBackupNotificationsEnabled();
    }

    /**
     * Dispatch the appropriate notification based on the stream type.
     *
     * @throws InvalidArgumentException If an unsupported notification type is encountered.
     */
    public function dispatchNotification(NotificationStream $notificationStream, BackupTaskLog $backupTaskLog, string $queue): void
    {
        $streamValue = (string) $notificationStream->getAttribute('value');

        /* This is one of the additional fields, out of two, to supply additional data for the external service to operate. */
        $additionalStreamValueOne = (string) $notificationStream->getAttribute('additional_field_one');

        match ($notificationStream->getAttribute('type')) {
            NotificationStream::TYPE_EMAIL => $this->sendEmailNotification($backupTaskLog, $streamValue),
            NotificationStream::TYPE_DISCORD => SendDiscordNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            NotificationStream::TYPE_SLACK => SendSlackNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            NotificationStream::TYPE_TEAMS => SendTeamsNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            NotificationStream::TYPE_PUSHOVER => SendPushoverNotificationJob::dispatch($this, $backupTaskLog, $streamValue, $additionalStreamValueOne)->onQueue($queue),
            NotificationStream::TYPE_TELEGRAM => SendTelegramNotificationJob::dispatch($this, $backupTaskLog, $streamValue)->onQueue($queue),
            default => throw new InvalidArgumentException("Unsupported notification type: {$notificationStream->getAttribute('type')}"),
        };
    }

    /**
     * Send an email notification for the backup task.
     */
    public function sendEmailNotification(BackupTaskLog $backupTaskLog, string $emailAddress): void
    {
        Mail::to($emailAddress)
            ->queue(new OutputMail($backupTaskLog));
    }

    /**
     * Send a Discord webhook notification for the backup task.
     *
     * @throws RuntimeException|ConnectionException If the Discord webhook request fails.
     */
    public function sendDiscordWebhookNotification(BackupTaskLog $backupTaskLog, string $webhookURL): void
    {
        $status = $backupTaskLog->getAttribute('successful_at') ? 'success' : 'failure';
        $message = $backupTaskLog->getAttribute('successful_at')
            ? 'The backup task was successful. Please see the details below for more information about this task.'
            : 'The backup task failed. Please see the details below for more information about this task.';
        $color = $backupTaskLog->getAttribute('successful_at') ? 3066993 : 15158332; // Green for success, Red for failure

        $embed = [
            'title' => $this->label . ' Backup Task',
            'description' => $message,
            'color' => $color,
            'fields' => [
                [
                    'name' => __('Backup Type'),
                    'value' => ucfirst($this->type),
                    'inline' => true,
                ],
                [
                    'name' => __('Remote Server'),
                    'value' => $this->remoteServer?->getAttribute('label'),
                    'inline' => true,
                ],
                [
                    'name' => __('Backup Destination'),
                    'value' => $this->backupDestination?->getAttribute('label') . ' (' . $this->backupDestination?->type() . ')',
                    'inline' => true,
                ],
                [
                    'name' => __('Result'),
                    'value' => ucfirst($status),
                    'inline' => true,
                ],
                [
                    'name' => __('Ran at'),
                    'value' => Carbon::parse($backupTaskLog->getAttribute('created_at'))->format('jS F Y, H:i:s'),
                    'inline' => true,
                ],
            ],
            'footer' => [
                'icon_url' => asset('notification-streams-assets/images/icon.png'),
                'text' => __('This notification was sent by :app.', ['app' => config('app.name')]),
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($webhookURL, [
            'username' => config('app.name'),
            'avatar_url' => asset('notification-streams-assets/images/icon.png'),
            'embeds' => [$embed],
        ]);

        if ($response->status() !== 204) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['message'] ?? 'Unknown error';
            throw new RuntimeException("Discord webhook failed: {$errorMessage}");
        }
    }

    /**
     * Send a Slack webhook notification for the backup task.
     *
     * @throws RuntimeException|ConnectionException If the Slack webhook request fails.
     */
    public function sendSlackWebhookNotification(BackupTaskLog $backupTaskLog, string $webhookURL): void
    {
        $status = $backupTaskLog->getAttribute('successful_at') ? 'success' : 'failure';
        $message = $backupTaskLog->getAttribute('successful_at')
            ? 'The backup task was successful. Please see the details below for more information about this task.'
            : 'The backup task failed. Please see the details below for more information about this task.';
        $color = $backupTaskLog->getAttribute('successful_at') ? 'good' : 'danger'; // Green for success, Red for failure

        $payload = [
            'attachments' => [
                [
                    'title' => $this->label . ' Backup Task',
                    'text' => $message,
                    'color' => $color,
                    'fields' => [
                        [
                            'title' => __('Backup Type'),
                            'value' => ucfirst($this->type),
                            'short' => true,
                        ],
                        [
                            'title' => __('Remote Server'),
                            'value' => $this->remoteServer?->label,
                            'short' => true,
                        ],
                        [
                            'title' => __('Backup Destination'),
                            'value' => $this->backupDestination?->label . ' (' . $this->backupDestination?->type() . ')',
                            'short' => true,
                        ],
                        [
                            'title' => __('Result'),
                            'value' => ucfirst($status),
                            'short' => true,
                        ],
                        [
                            'title' => __('Ran at'),
                            'value' => Carbon::parse($backupTaskLog->getAttribute('created_at'))->format('jS F Y, H:i:s'),
                            'short' => true,
                        ],
                    ],
                    'footer' => __('This notification was sent by :app.', ['app' => config('app.name')]),
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($webhookURL, $payload);

        if ($response->status() !== 200 || $response->body() !== 'ok') {
            throw new RuntimeException('Slack webhook failed: ' . $response->body());
        }
    }

    /**
     * Send a Microsoft Teams webhook notification for the backup task.
     *
     * @throws RuntimeException|ConnectionException If the Teams webhook request fails.
     */
    public function sendTeamsWebhookNotification(BackupTaskLog $backupTaskLog, string $webhookURL): void
    {
        $status = $backupTaskLog->getAttribute('successful_at') ? 'success' : 'failure';
        $message = $backupTaskLog->getAttribute('successful_at')
            ? 'The backup task was successful. Please see the details below for more information about this task.'
            : 'The backup task failed. Please see the details below for more information about this task.';
        $color = $backupTaskLog->getAttribute('successful_at') ? '00FF00' : 'FF0000'; // Green for success, Red for failure

        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => $color,
            'summary' => $this->label . ' Backup Task',
            'sections' => [
                [
                    'activityTitle' => $this->label . ' Backup Task',
                    'activitySubtitle' => $message,
                    'facts' => [
                        [
                            'name' => __('Backup Type'),
                            'value' => ucfirst($this->type),
                        ],
                        [
                            'name' => __('Remote Server'),
                            'value' => $this->remoteServer?->label,
                        ],
                        [
                            'name' => __('Backup Destination'),
                            'value' => $this->backupDestination?->label . ' (' . $this->backupDestination?->type() . ')',
                        ],
                        [
                            'name' => __('Result'),
                            'value' => ucfirst($status),
                        ],
                        [
                            'name' => __('Ran at'),
                            'value' => Carbon::parse($backupTaskLog->getAttribute('created_at'))->format('jS F Y, H:i:s'),
                        ],
                    ],
                    'text' => __('This notification was sent by :app.', ['app' => config('app.name')]),
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($webhookURL, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Teams webhook failed: ' . $response->body());
        }
    }

    /**
     * Send a Pushover notification for a backup task.
     *
     * @param  BackupTaskLog  $backupTaskLog  The log entry for the backup task
     * @param  string  $pushoverToken  The Pushover API token
     */
    public function sendPushoverNotification(BackupTaskLog $backupTaskLog, string $pushoverToken, string $userToken): void
    {
        $isSuccessful = $backupTaskLog->getAttribute('successful_at') !== null;
        $status = $isSuccessful ? 'success' : 'failure';
        $message = $isSuccessful
            ? 'The backup task was successful.'
            : 'The backup task failed.';
        $priority = $isSuccessful ? 0 : 1; // Normal priority for success, high priority for failure

        $payload = [
            'token' => $pushoverToken,
            'user' => $userToken,
            'title' => "{$this->label} Backup Task: " . ucfirst($status),
            'message' => $message . " Details:\n" .
                'Backup Type: ' . ucfirst($this->type) . "\n" .
                'Remote Server: ' . ($this->remoteServer?->label ?? 'N/A') . "\n" .
                'Backup Destination: ' . ($this->backupDestination?->label ?? 'N/A') .
                ' (' . ($this->backupDestination?->type() ?? 'N/A') . ")\n" .
                'Ran at: ' . Carbon::parse($backupTaskLog->getAttribute('created_at'))->format('jS F Y, H:i:s'),
            'priority' => $priority,
        ];

        $response = Http::post('https://api.pushover.net/1/messages.json', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Pushover notification failed: ' . $response->body());
        }
    }

    /**
     * Send a Telegram notification for the backup task.
     *
     * @param  BackupTaskLog  $backupTaskLog  The log entry for the backup task
     * @param  string  $chatID  The target Telegram chat ID
     *
     * @throws RuntimeException If the Telegram request fails.
     */
    public function sendTelegramNotification(BackupTaskLog $backupTaskLog, string $chatID): void
    {
        $url = $this->getTelegramUrl();
        $message = $this->composeTelegramNotificationText($this, $backupTaskLog);
        $payload = [
            'chat_id' => $chatID,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        $response = Http::post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Telegram notification failed: ' . $response->body());
        }
    }
}
