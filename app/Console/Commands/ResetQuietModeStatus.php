<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\User\QuietModeExpiredMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * Class ResetQuietModeStatus
 *
 * This command resets the quiet mode for users whose quiet mode has expired.
 * It processes users in chunks to efficiently handle large datasets.
 */
class ResetQuietModeStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vanguard:reset-quiet-mode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the quiet mode for users whose quiet mode has expired.';

    /**
     * The count of users whose quiet mode was reset.
     */
    protected int $resetCount = 0;

    /**
     * Execute the console command.
     *
     * This method initiates the process of resetting expired quiet modes,
     * provides informational output, and returns the command's exit status.
     */
    public function handle(): int
    {
        $this->components->info('Starting to reset expired quiet modes...');

        $this->resetExpiredQuietModes();

        $this->components->info("Quiet mode reset for {$this->resetCount} users.");

        return CommandAlias::SUCCESS;
    }

    /**
     * Reset quiet mode for users whose quiet mode has expired.
     *
     * This method queries the database for users with expired quiet mode
     * and processes them in chunks to efficiently handle large datasets.
     */
    protected function resetExpiredQuietModes(): void
    {
        User::query()
            ->whereDate('quiet_until', '<=', Carbon::today())
            ->whereNotNull('quiet_until')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $this->resetUserQuietMode($user);
                }
            });
    }

    /**
     * Reset quiet mode for a single user and increment the reset count.
     *
     * This method clears the quiet mode for the given user, increments the reset count,
     * and logs an informational message about the action. It handles both User models
     * and generic Eloquent models for flexibility.
     *
     * @param  User|Model  $user  The user whose quiet mode is being reset
     */
    protected function resetUserQuietMode(User|Model $user): void
    {
        if ($user instanceof User) {
            $user->clearQuietMode();

            Mail::to($user)->queue(new QuietModeExpiredMail($user));

            $this->resetCount++;

            $this->info("Quiet mode reset for user: {$user->getAttribute('email')}");
        } else {
            $this->warn('Unexpected model type encountered. Expected User, got ' . $user::class);
        }
    }
}
