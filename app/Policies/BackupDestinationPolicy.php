<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BackupDestination;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BackupDestinationPolicy
{
    public function view(User $user, BackupDestination $backupDestination): Response
    {
        return $user->id === $backupDestination->user_id
            ? Response::allow()
            : Response::deny('You do not own this backup destination.');
    }

    public function update(User $user, BackupDestination $backupDestination): Response
    {
        return $user->id === $backupDestination->user_id
            ? Response::allow()
            : Response::deny('You do not own this backup destination.');
    }

    public function forceDelete(User $user, BackupDestination $backupDestination): Response
    {
        return $user->id === $backupDestination->user_id
            ? Response::allow()
            : Response::deny('You do not own this backup destination.');
    }
}
