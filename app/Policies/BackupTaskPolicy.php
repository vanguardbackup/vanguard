<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BackupTask;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BackupTaskPolicy
{
    public function view(User $user, BackupTask $backupTask): Response
    {
        return $user->getAttribute('id') === $backupTask->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this backup task.');
    }

    public function update(User $user, BackupTask $backupTask): Response
    {
        return $user->getAttribute('id') === $backupTask->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this backup task.');
    }

    public function forceDelete(User $user, BackupTask $backupTask): Response
    {
        return $user->getAttribute('id') === $backupTask->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this backup task.');
    }
}
