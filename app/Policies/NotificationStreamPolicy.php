<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NotificationStream;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy for authorizing actions on NotificationStream models.
 *
 * This policy defines authorization rules for viewing, updating,
 * and force deleting NotificationStream instances.
 */
class NotificationStreamPolicy
{
    /**
     * Determine whether the user can view the notification stream.
     *
     * @param  User  $user  The user attempting to view the notification stream
     * @param  NotificationStream  $notificationStream  The notification stream being viewed
     * @return Response The authorization response
     */
    public function view(User $user, NotificationStream $notificationStream): Response
    {
        return $user->getAttribute('id') === $notificationStream->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this notification stream.');
    }

    /**
     * Determine whether the user can update the notification stream.
     *
     * @param  User  $user  The user attempting to update the notification stream
     * @param  NotificationStream  $notificationStream  The notification stream being updated
     * @return Response The authorization response
     */
    public function update(User $user, NotificationStream $notificationStream): Response
    {
        return $user->getAttribute('id') === $notificationStream->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this notification stream.');
    }

    /**
     * Determine whether the user can force delete the notification stream.
     *
     * @param  User  $user  The user attempting to force delete the notification stream
     * @param  NotificationStream  $notificationStream  The notification stream being force deleted
     * @return Response The authorization response
     */
    public function forceDelete(User $user, NotificationStream $notificationStream): Response
    {
        return $user->getAttribute('id') === $notificationStream->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this notification stream.');
    }
}
