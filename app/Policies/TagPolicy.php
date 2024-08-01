<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy for authorizing actions on Tag models.
 *
 * This policy defines authorization rules for viewing, updating,
 * and force deleting Tag instances.
 */
class TagPolicy
{
    /**
     * Determine whether the user can view the tag.
     *
     * @param  User  $user  The user attempting to view the tag
     * @param  Tag  $tag  The tag being viewed
     * @return Response The authorization response
     */
    public function view(User $user, Tag $tag): Response
    {
        return $user->getAttribute('id') === $tag->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this tag.');
    }

    /**
     * Determine whether the user can update the tag.
     *
     * @param  User  $user  The user attempting to update the tag
     * @param  Tag  $tag  The tag being updated
     * @return Response The authorization response
     */
    public function update(User $user, Tag $tag): Response
    {
        return $user->getAttribute('id') === $tag->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this tag.');
    }

    /**
     * Determine whether the user can force delete the tag.
     *
     * @param  User  $user  The user attempting to force delete the tag
     * @param  Tag  $tag  The tag being force deleted
     * @return Response The authorization response
     */
    public function forceDelete(User $user, Tag $tag): Response
    {
        return $user->getAttribute('id') === $tag->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this tag.');
    }
}
