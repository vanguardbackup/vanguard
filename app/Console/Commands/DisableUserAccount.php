<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DisableUserAccount extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vanguard:disable-user-account {user : The ID of the user}';

    /**
     * The console command description.
     */
    protected $description = 'Disable a user account and clear their sessions.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $userId = $this->argument('user');

        if (! is_numeric($userId)) {
            $this->components->error('The value provided is not an id.');

            return;
        }

        $userId = (int) $userId;

        $user = User::whereId($userId)->first();

        if (! $user) {
            $this->components->error("User with ID {$userId} not found.");

            return;
        }

        if ($user->isAdmin()) {
            $this->components->error('Cannot disable an admin account.');

            return;
        }

        if ($user->hasDisabledAccount()) {
            $this->components->info('User account is already disabled.');

            return;
        }

        DB::transaction(function () use ($user): void {
            $user->disableUserAccount();
            $this->clearUserSessions($user);
        });

        $this->components->info('User account has been disabled and all sessions cleared.');

    }

    /**
     * Clear all sessions for the given user.
     */
    private function clearUserSessions(User $user): void
    {
        $sessionDriver = Config::get('session.driver');

        match ($sessionDriver) {
            'database' => $this->clearDatabaseSessions($user),
            'redis' => $this->clearRedisSessions($user),
            'file' => $this->clearFileSessions($user),
            default => $this->components->warn("Session clearing not implemented for driver: {$sessionDriver}"),
        };
    }

    /**
     * Clear database sessions for the given user.
     */
    private function clearDatabaseSessions(User $user): void
    {
        DB::table(Config::get('session.table', 'sessions'))
            ->where('user_id', $user->getAttribute('id'))
            ->delete();
    }

    /**
     * Clear Redis sessions for the given user.
     */
    private function clearRedisSessions(User $user): void
    {
        $prefix = Config::get('session.prefix', '');
        $pattern = "{$prefix}:*";

        $connection = Redis::connection(Config::get('session.connection'));
        $keys = $connection->keys($pattern);

        if (is_array($keys)) {
            foreach ($keys as $key) {
                $session = $connection->get($key);
                if (is_string($session) && str_contains($session, "\"user_id\";i:{$user->getAttribute('id')};")) {
                    $connection->del($key);
                }
            }
        }
    }

    /**
     * Clear file sessions for the given user.
     */
    private function clearFileSessions(User $user): void
    {
        $directory = Config::get('session.files');
        $pattern = "{$directory}/sess_*";

        $files = glob($pattern);
        if (is_array($files)) {
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if (is_string($content) && str_contains($content, "\"user_id\";i:{$user->getAttribute('id')};")) {
                    unlink($file);
                }
            }
        }
    }
}
