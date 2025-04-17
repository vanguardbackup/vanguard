<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\SuspensionLiftedMail;
use App\Models\UserSuspension;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LiftSuspensionsOnUserJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $suspensionId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $suspension = UserSuspension::find($this->suspensionId);

        if (! $suspension || $suspension->lifted_at) {
            Log::warning('Unsuspending user "' . $this->suspensionId . '" does not exist.');

            return;
        }

        $shouldNotify = $suspension->notify_user_upon_suspension_being_lifted_at;

        if ($shouldNotify) {
            $user = $suspension->user;
            Mail::to($user->email)->send(new SuspensionLiftedMail($user, $suspension));
        }

        $suspension->forceFill(['lifted_at' => now()]);
        $suspension->save();
    }
}
