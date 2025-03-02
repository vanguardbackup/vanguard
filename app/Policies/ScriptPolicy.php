<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Script;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy for authorizing actions on Script models.
 *
 * This policy defines authorization rules for viewing, updating,
 * and force deleting Script instances.
 */
class ScriptPolicy
{
    /**
     * Determine whether the user can view the script.
     *
     * @param  User  $user  The user attempting to view the script
     * @param  Script  $script  The script being viewed
     * @return Response The authorization response
     */
    public function view(User $user, Script $script): Response
    {
        return $user->getAttribute('id') === $script->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this script.');
    }

    /**
     * Determine whether the user can update the script.
     *
     * @param  User  $user  The user attempting to update the script
     * @param  Script  $script  The script being updated
     * @return Response The authorization response
     */
    public function update(User $user, Script $script): Response
    {
        return $user->getAttribute('id') === $script->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this script.');
    }

    /**
     * Determine whether the user can force delete the script.
     *
     * @param  User  $user  The user attempting to force delete the script
     * @param  Script  $script  The script being force deleted
     * @return Response The authorization response
     */
    public function forceDelete(User $user, Script $script): Response
    {
        return $user->getAttribute('id') === $script->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this script.');
    }
}
