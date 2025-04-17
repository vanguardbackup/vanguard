<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\LiftSuspensionsOnUserJob;
use App\Models\UserSuspension;
use Illuminate\Console\Command;

class UnsuspendUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vanguard:unsuspend-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks if there are any users that need to be unsuspended.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $suspensions = UserSuspension::whereDate('suspended_until', '<=', now())
            ->whereNull('lifted_at')
            ->get();

        $this->components->info("Found {$suspensions->count()} suspensions to lift.");

        if ($suspensions->isEmpty()) {
            return;
        }

        foreach ($suspensions as $suspension) {
            LiftSuspensionsOnUserJob::dispatch($suspension->id);
        }

        $this->components->info('Dispatched jobs to lift suspensions.');
    }
}
