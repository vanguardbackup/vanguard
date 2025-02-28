<?php

namespace App\Models\Traits;

use App\Models\NotificationStream;

trait HasNotificationStream
{
    /**
     * Check if the task has email notifications enabled.
     */
    public function hasEmailNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_EMAIL)
            ->exists();
    }

    /**
     * Check if the task has Discord notifications enabled.
     */
    public function hasDiscordNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_DISCORD)
            ->exists();
    }

    /**
     * Check if the task has Slack notifications enabled.
     */
    public function hasSlackNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_SLACK)
            ->exists();
    }

    /**
     * Check if the task has Microsoft Teams notifications enabled.
     */
    public function hasTeamNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_TEAMS)
            ->exists();
    }

    /**
     * Check if the task has Pushover notifications enabled.
     */
    public function hasPushoverNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_PUSHOVER)
            ->exists();
    }

    /**
     * Check if the task has Telegram notifications enabled.
     */
    public function hasTelegramNotification(): bool
    {
        return $this->notificationStreams()
            ->where('type', NotificationStream::TYPE_TELEGRAM)
            ->exists();
    }
}
