<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\User\TwoFactor\LongstandingTwoFactorFollowUpMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Command\Command as CommandAlias;

class NotifyUsersAboutOldBackupCodes extends Command
{
    /**
     * The console command name and signature.
     */
    protected $signature = 'vanguard:notify-old-backup-codes';

    /**
     * The console command description.
     */
    protected $description = 'Notify users with outdated two-factor backup codes via email.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $usersWithOldBackupCodes = $this->getUsersWithOldBackupCodes();
        $emailCount = $this->notifyUsers($usersWithOldBackupCodes);

        $this->logNotificationResult($emailCount);

        return CommandAlias::SUCCESS;
    }

    /**
     * Get users with old backup codes.
     *
     * @return Collection<int, User>
     */
    private function getUsersWithOldBackupCodes(): Collection
    {
        return User::withOutdatedBackupCodes()->get();
    }

    /**
     * Send notification emails to users.
     *
     * @param  Collection<int, User>  $users
     */
    private function notifyUsers(Collection $users): int
    {
        $emailCount = 0;

        $users->each(function (User $user) use (&$emailCount): void {
            Mail::to($user)->queue(new LongstandingTwoFactorFollowUpMail($user));
            $emailCount++;
        });

        return $emailCount;
    }

    /**
     * Log the notification result.
     */
    private function logNotificationResult(int $emailCount): void
    {
        if ($emailCount > 0) {
            Log::info("Sent {$emailCount} users emails about their outdated backup codes.");
        }
    }
}
