<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NotificationStreamFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Represents a notification stream in the system.
 *
 * This model handles different types of notification streams (email, Discord, Slack, Teams)
 * and their relationships with users and backup tasks.
 */
class NotificationStream extends Model
{
    /** @use HasFactory<NotificationStreamFactory> */
    use HasFactory;

    public const string TYPE_EMAIL = 'email';
    public const string TYPE_DISCORD = 'discord_webhook';
    public const string TYPE_SLACK = 'slack_webhook';
    public const string TYPE_TEAMS = 'teams_webhook';
    public const string TYPE_PUSHOVER = 'pushover';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'formatted_type',
        'type_icon',
    ];

    /**
     * Get the user that owns the notification stream.
     *
     * @return BelongsTo<User, NotificationStream>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the backup tasks that are linked to the notification stream.
     *
     * @return BelongsToMany<BackupTask>
     */
    public function backupTasks(): BelongsToMany
    {
        return $this->belongsToMany(BackupTask::class, 'backup_task_notification_streams')
            ->withTimestamps();
    }

    /**
     * Check if the notification stream type is email.
     */
    public function isEmail(): bool
    {
        return $this->type === self::TYPE_EMAIL;
    }

    /**
     * Check if the notification stream type is Discord.
     */
    public function isDiscord(): bool
    {
        return $this->type === self::TYPE_DISCORD;
    }

    /**
     * Check if the notification stream type is Slack.
     */
    public function isSlack(): bool
    {
        return $this->type === self::TYPE_SLACK;
    }

    /**
     * Check if the notification stream type is Microsoft Teams.
     */
    public function isTeams(): bool
    {
        return $this->type === self::TYPE_TEAMS;
    }

    /**
     * Check if the notification stream type is Pushover.
     */
    public function isPushover(): bool
    {
        return $this->type === self::TYPE_PUSHOVER;
    }

    /**
     * Returns whether this stream will send backup notifications on success.
     */
    public function hasSuccessfulBackupNotificationsEnabled(): bool
    {
        return (bool) $this->getAttribute('receive_successful_backup_notifications');
    }

    /**
     * Returns whether this stream will send backup notifications on failure.
     */
    public function hasFailedBackupNotificationsEnabled(): bool
    {
        return (bool) $this->getAttribute('receive_failed_backup_notifications');
    }

    /**
     * Get the formatted type of the notification stream.
     *
     * @return Attribute<string|null, never>
     */
    protected function formattedType(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                return match ((string) $this->type) {
                    self::TYPE_EMAIL => (string) __('Email'),
                    self::TYPE_DISCORD => (string) __('Discord Webhook'),
                    self::TYPE_SLACK => (string) __('Slack Webhook'),
                    self::TYPE_TEAMS => (string) __('Teams Webhook'),
                    self::TYPE_PUSHOVER => (string) __('Pushover'),
                    default => null,
                };
            }
        );
    }

    /**
     * Get the SVG icon path for the notification stream type.
     *
     * @return Attribute<string, never>
     */
    protected function typeIcon(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return match ($this->type) {
                    self::TYPE_EMAIL => 'M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z',
                    self::TYPE_DISCORD => 'M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189Z',
                    self::TYPE_SLACK => 'M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z',
                    self::TYPE_TEAMS => 'M 12.5 2 A 3 3 0 0 0 9.7089844 6.09375 C 9.4804148 6.0378189 9.2455412 6 9 6 L 4 6 C 2.346 6 1 7.346 1 9 L 1 14 C 1 15.654 2.346 17 4 17 L 9 17 C 10.654 17 12 15.654 12 14 L 12 9 C 12 8.6159715 11.921192 8.2518913 11.789062 7.9140625 A 3 3 0 0 0 12.5 8 A 3 3 0 0 0 12.5 2 z M 19 4 A 2 2 0 0 0 19 8 A 2 2 0 0 0 19 4 z M 4.5 9 L 8.5 9 C 8.776 9 9 9.224 9 9.5 C 9 9.776 8.776 10 8.5 10 L 7 10 L 7 14 C 7 14.276 6.776 14.5 6.5 14.5 C 6.224 14.5 6 14.276 6 14 L 6 10 L 4.5 10 C 4.224 10 4 9.776 4 9.5 C 4 9.224 4.224 9 4.5 9 z M 15 9 C 14.448 9 14 9.448 14 10 L 14 14 C 14 16.761 11.761 19 9 19 C 8.369 19 8.0339375 19.755703 8.4609375 20.220703 C 9.4649375 21.313703 10.903 22 12.5 22 C 15.24 22 17.529453 20.040312 17.939453 17.320312 C 17.979453 17.050312 18 16.78 18 16.5 L 18 11 C 18 9.9 17.1 9 16 9 L 15 9 z M 20.888672 9 C 20.322672 9 19.870625 9.46625 19.890625 10.03125 C 19.963625 12.09325 20 16.5 20 16.5 C 20 16.618 19.974547 16.859438 19.935547 17.148438 C 19.812547 18.048438 20.859594 18.653266 21.558594 18.072266 C 22.439594 17.340266 23 16.237 23 15 L 23 11 C 23 9.9 22.1 9 21 9 L 20.888672 9 z',
                    self::TYPE_PUSHOVER => 'M11.6685 21.0473c5.2435.1831 9.6426-3.9191 9.8257-9.1627.1831-5.24355-3.9191-9.64267-9.1626-9.82578-5.24355-.18311-9.64265 3.91918-9.82576 9.16268-.18311 5.2435 3.91916 9.6427 9.16266 9.8258zM11.8206 8.47095l1.9374-.1867-2.0265 4.17345c.331-.0144.6576-.1144.9816-.3018.324-.1873.6257-.4274.9014-.7186.2775-.291.5191-.6168.7267-.9791.2075-.3603.3593-.7189.457-1.0701.0577-.2189.0892-.4295.0926-.6317s-.0442-.3822-.1409-.5378c-.0967-.1556-.2463-.2834-.4508-.3833-.2044-.1-.4828-.1561-.8389-.1686-.4153-.0145-.8256.038-1.2309.1594-.4071.1213-.7848.3049-1.1369.5507-.352.2458-.66.5562-.9274.933-.2676.3769-.4646.8082-.5911 1.2939-.0483.1598-.0768.2869-.0895.3849s-.0174.1776-.014.2408c.0015.0632.009.1136.0208.1474.0119.0339.0218.0676.028.1031-.4321-.015-.7443-.1132-.9366-.2926-.1924-.1794-.23-.4852-.1149-.9119.1177-.4452.3625-.8637.7364-1.2572.374-.3936.8129-.73655 1.3188-1.02705.5059-.29055 1.0559-.51825 1.6501-.67945.5942-.1612 1.1704-.2321 1.7285-.2126.4914.0172.9006.102 1.2295.2527.329.1508.5839.3453.7614.5799.1775.2346.2849.5057.3207.8114.0358.3057.0099.6223-.0777.9497-.1066.3936-.2967.7879-.5685 1.18135s-.609.7455-1.0079 1.0565c-.4008.3128-.8551.5605-1.3666.7469-.5096.1846-1.049.2679-1.6164.248l-.0631-.0022-1.7377 3.5597-1.82655-.0638z',
                    default => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z',
                };
            }
        );
    }

    /**
     * Get the casts array for the model's attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'receive_successful_backup_notifications' => 'bool',
            'receive_failed_backup_notifications' => 'bool',
        ];
    }
}
