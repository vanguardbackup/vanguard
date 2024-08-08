<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BackupDestination;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy for authorizing actions on BackupDestination models for HTTP requests.
 *
 * This policy defines authorization rules for viewing, updating,
 * and deleting BackupDestination instances for authenticated users.
 */
class BackupDestinationPolicy
{
    /**
     * Determine whether the user can view any backup destinations.
     *
     * @param  User  $user  The user attempting to view backup destinations
     * @return Response The authorization response
     */
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

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
            : Response::deny('You do not have permission to view this backup destination.');
    }

    /**
     * Determine whether the user can create backup destinations.
     *
     * @param  User  $user  The user attempting to create a backup destination
     * @return Response The authorization response
     */
    public function create(User $user): Response
    {
        return Response::allow();
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
            : Response::deny('You do not have permission to update this backup destination.');
    }

    /**
     * Determine whether the user can delete the backup destination.
     *
     * @param  User  $user  The user attempting to delete the backup destination
     * @param  BackupDestination  $backupDestination  The backup destination being deleted
     * @return Response The authorization response
     */
    public function delete(User $user, BackupDestination $backupDestination): Response
    {
        return $user->getAttribute('id') === $backupDestination->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not have permission to delete this backup destination.');
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
            : Response::deny('You do not have permission to force delete this backup destination.');
    }
}
