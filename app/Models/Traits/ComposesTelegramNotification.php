<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Carbon\Carbon;
use RuntimeException;

trait ComposesTelegramNotification
{
    /**
     * Build URL for Telegram notification.
     *
     * @throws RuntimeException
     */
    public function getTelegramUrl(): string
    {
        $config = config()->get('services.telegram');

        if ($config['bot_token'] === null) {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        return 'https://api.telegram.org/bot' . $config['bot_token'] . '/sendMessage';
    }

    /**
     * Compose message for Telegram notification based on the backup task and its log.
     */
    public function composeTelegramNotificationText(BackupTask $backupTask, BackupTaskLog $backupTaskLog): string
    {
        $isSuccessful = $backupTaskLog->getAttribute('successful_at') !== null;
        $message = $isSuccessful
            ? 'The backup task was <b>SUCCESSFUL</b>. 👌'
            : 'The backup task <b>FAILED</b>. 😭';

        return $message . "\nDetails:\n" .
            'Backup Type: ' . ucfirst($backupTask->getAttribute('type')) . "\n" .
            'Remote Server: ' . ($backupTask->getAttribute('remoteServer')?->label ?? 'N/A') . "\n" .
            'Backup Destination: ' . ($backupTask->getAttribute('backupDestination')?->label ?? 'N/A') .
            ' (' . ($backupTask->getAttribute('backupDestination')?->type() ?? 'N/A') . ")\n" .
            'Ran at: ' . Carbon::parse($backupTaskLog->getAttribute('created_at'))->format('jS F Y, H:i:s');
    }
}
