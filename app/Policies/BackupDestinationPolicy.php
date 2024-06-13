<?php

namespace App\Policies;

use App\Models\BackupDestination;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BackupDestinationPolicy
{
    public function viewAny(User $user): bool
    {
        //
    }

    public function view(User $user, BackupDestination $backupDestination): Response
    {
        return $user->id === $backupDestination->user_id
            ? Response::allow()
            : Response::deny('You do not own this backup destination.');
    }

    public function create(User $user): bool
    {
        //
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
