<?php

namespace App\Policies;

use App\Models\BackupTask;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BackupTaskPolicy
{
    public function viewAny(User $user): bool
    {
        //
    }

    public function view(User $user, BackupTask $backupTask): Response
    {
        return $user->id === $backupTask->user_id
            ? Response::allow()
            : Response::deny('You do not own this backup task.');
    }

    public function create(User $user): bool
    {
        //
    }

    public function update(User $user, BackupTask $backupTask): Response
    {
        return $user->id === $backupTask->user_id
            ? Response::allow()
            : Response::deny('You do not own this backup task.');
    }

    public function forceDelete(User $user, BackupTask $backupTask): Response
    {
        return $user->id === $backupTask->user_id
            ? Response::allow()
            : Response::deny('You do not own this backup task.');
    }
}
