<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SuspendUserAccount extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vanguard:disable-user-account {user : The ID of the user}';

    /**
     * The console command description.
     */
    protected $description = 'Permanently suspend a user account.';

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
            $this->components->error('Cannot suspend an administrator account.');

            return;
        }

        if ($user->hasSuspendedAccount()) {
            $this->components->info('User account is already suspended.');

            return;
        }

        DB::transaction(function () use ($user): void {

            $user->suspensions()->create([
                'user_id' => $user->id,
                'suspended_at' => now(),
                'suspended_until' => null, // permanent!
                'suspended_reason' => 'Manual Suspension',
                'private_note' => 'This suspension was performed with an Artisan Command.',
            ]);

            purge_user_sessions($user);
        });

        $this->components->success('User account has been suspended and all sessions cleared.');

    }
}
