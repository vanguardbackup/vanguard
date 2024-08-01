<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BackupTask;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy for authorizing actions on BackupTask models.
 *
 * This policy defines authorization rules for viewing, updating,
 * and force deleting BackupTask instances.
 */
class BackupTaskPolicy
{
    /**
     * Determine whether the user can view the backup task.
     *
     * @param  User  $user  The user attempting to view the backup task
     * @param  BackupTask  $backupTask  The backup task being viewed
     * @return Response The authorization response
     */
    public function view(User $user, BackupTask $backupTask): Response
    {
        return $user->getAttribute('id') === $backupTask->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this backup task.');
    }

    /**
     * Determine whether the user can update the backup task.
     *
     * @param  User  $user  The user attempting to update the backup task
     * @param  BackupTask  $backupTask  The backup task being updated
     * @return Response The authorization response
     */
    public function update(User $user, BackupTask $backupTask): Response
    {
        return $user->getAttribute('id') === $backupTask->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this backup task.');
    }

    /**
     * Determine whether the user can force delete the backup task.
     *
     * @param  User  $user  The user attempting to force delete the backup task
     * @param  BackupTask  $backupTask  The backup task being force deleted
     * @return Response The authorization response
     */
    public function forceDelete(User $user, BackupTask $backupTask): Response
    {
        return $user->getAttribute('id') === $backupTask->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this backup task.');
    }
}
