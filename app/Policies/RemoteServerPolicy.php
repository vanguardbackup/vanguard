<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RemoteServer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy for authorizing actions on RemoteServer models.
 *
 * This policy defines authorization rules for viewing, updating,
 * and force deleting RemoteServer instances.
 */
class RemoteServerPolicy
{
    /**
     * Determine whether the user can view the remote server.
     *
     * @param  User  $user  The user attempting to view the remote server
     * @param  RemoteServer  $remoteServer  The remote server being viewed
     * @return Response The authorization response
     */
    public function view(User $user, RemoteServer $remoteServer): Response
    {
        return $user->getAttribute('id') === $remoteServer->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this remote server.');
    }

    /**
     * Determine whether the user can update the remote server.
     *
     * @param  User  $user  The user attempting to update the remote server
     * @param  RemoteServer  $remoteServer  The remote server being updated
     * @return Response The authorization response
     */
    public function update(User $user, RemoteServer $remoteServer): Response
    {
        return $user->getAttribute('id') === $remoteServer->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this remote server.');
    }

    /**
     * Determine whether the user can force delete the remote server.
     *
     * @param  User  $user  The user attempting to force delete the remote server
     * @param  RemoteServer  $remoteServer  The remote server being force deleted
     * @return Response The authorization response
     */
    public function forceDelete(User $user, RemoteServer $remoteServer): Response
    {
        return $user->getAttribute('id') === $remoteServer->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this remote server.');
    }
}
