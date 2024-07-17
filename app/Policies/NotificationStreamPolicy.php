<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NotificationStream;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotificationStreamPolicy
{
    public function view(User $user, NotificationStream $notificationStream): Response
    {
        return $user->getAttribute('id') === $notificationStream->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this notification stream.');
    }

    public function update(User $user, NotificationStream $notificationStream): Response
    {
        return $user->getAttribute('id') === $notificationStream->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this notification stream.');
    }

    public function forceDelete(User $user, NotificationStream $notificationStream): Response
    {
        return $user->getAttribute('id') === $notificationStream->getAttribute('user_id')
            ? Response::allow()
            : Response::deny('You do not own this notification stream.');
    }
}
