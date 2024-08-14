<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\PersonalAccessTokenExpiringSoonMail;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Command to queue notifications for personal access tokens expiring soon.
 *
 * This command identifies tokens that are expiring soon and have not been
 * recently notified about, then queues email notifications to the token owners.
 */
class SendPersonalAccessTokenExpiringSoon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vanguard:tokens-expiring-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue notifications for personal access tokens expiring soon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiringTokens = $this->getExpiringTokens();

        if ($expiringTokens->isEmpty()) {
            $this->components->info('No tokens requiring notifications.');

            return Command::SUCCESS;
        }

        $this->queueNotifications($expiringTokens);

        $this->components->info("Notifications queued for {$expiringTokens->count()} expiring tokens.");

        return Command::SUCCESS;
    }

    /**
     * Get tokens requiring notifications.
     *
     * @return Collection<int, PersonalAccessToken>
     */
    private function getExpiringTokens(): Collection
    {
        return PersonalAccessToken::needingNotification()->get();
    }

    /**
     * Queue notifications for expiring tokens.
     *
     * @param  Collection<int, PersonalAccessToken>  $tokens
     */
    private function queueNotifications(Collection $tokens): void
    {
        $tokens->each(fn (PersonalAccessToken $personalAccessToken) => $this->queueNotificationForToken($personalAccessToken));
    }

    /**
     * Queue a notification for a single token and update its last notification date.
     */
    private function queueNotificationForToken(PersonalAccessToken $personalAccessToken): void
    {
        $user = $this->getUserFromToken($personalAccessToken);

        if ($user instanceof User && $user->getAttribute('email')) {
            Mail::to($user)->queue(new PersonalAccessTokenExpiringSoonMail($personalAccessToken));
            $this->updateLastNotificationSent($personalAccessToken);
        }
    }

    /**
     * Get the user associated with the token.
     */
    private function getUserFromToken(PersonalAccessToken $personalAccessToken): ?User
    {
        return $personalAccessToken->getAttribute('tokenable') instanceof User ? $personalAccessToken->getAttribute('tokenable') : null;
    }

    /**
     * Update the last notification sent date for the token.
     */
    private function updateLastNotificationSent(PersonalAccessToken $personalAccessToken): void
    {
        $personalAccessToken->forceFill(['last_notification_sent_at' => now()])->save();
    }
}
