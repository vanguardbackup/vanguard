<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DisableTwoFactorAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vanguard:disable-two-factor
                        {email : The email address of the user.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will disable two-factor authentication for a user.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $user = User::whereEmail($this->argument('email'))->first();

        if (! $user) {
            $this->components->error("A user cannot be found with the email address '{$this->argument('email')}'");

            return;
        }

        if (! $user->hasTwoFactorEnabled()) {
            $this->components->error("{$user->name} has not enabled two-factor authentication.");

            return;
        }

        $user->disableTwoFactorAuth();

        $this->components->success("Disabled two-factor authentication for {$user->name}.");

    }
}
