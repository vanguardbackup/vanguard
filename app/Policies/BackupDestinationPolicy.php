<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BackupDestination;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy for authorizing actions on BackupDestination models.
 *
 * This policy defines authorization rules for viewing, updating,
 * and force deleting BackupDestination instances.
 */
class BackupDestinationPolicy
{
    /**
     * Determine whether the user can view the backup destination.
     *
     * @param  User  $user  The user attempting to view the backup destination
     * @param  BackupDestination  $backupDestination  The backup destination being viewed
     * @return Response The authorization response
     */
    public function view(User $user, BackupDestination $backupDestination): Response
    {
        return $user->getAttribute('id') === $backupDestination->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this backup destination.');
    }

    /**
     * Determine whether the user can update the backup destination.
     *
     * @param  User  $user  The user attempting to update the backup destination
     * @param  BackupDestination  $backupDestination  The backup destination being updated
     * @return Response The authorization response
     */
    public function update(User $user, BackupDestination $backupDestination): Response
    {
        return $user->getAttribute('id') === $backupDestination->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this backup destination.');
    }

    /**
     * Determine whether the user can force delete the backup destination.
     *
     * @param  User  $user  The user attempting to force delete the backup destination
     * @param  BackupDestination  $backupDestination  The backup destination being force deleted
     * @return Response The authorization response
     */
    public function forceDelete(User $user, BackupDestination $backupDestination): Response
    {
        return $user->getAttribute('id') === $backupDestination->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this backup destination.');
    }
}
