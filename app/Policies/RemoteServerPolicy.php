<?php

namespace App\Policies;

use App\Models\RemoteServer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RemoteServerPolicy
{
    public function view(User $user, RemoteServer $remoteServer): Response
    {
        return $user->id === $remoteServer->user_id
            ? Response::allow()
            : Response::deny('You do not own this remote server.');
    }

    public function update(User $user, RemoteServer $remoteServer): Response
    {
        return $user->id === $remoteServer->user_id
            ? Response::allow()
            : Response::deny('You do not own this remote server.');
    }

    public function forceDelete(User $user, RemoteServer $remoteServer): Response
    {
        return $user->id === $remoteServer->user_id
            ? Response::allow()
            : Response::deny('You do not own this remote server.');
    }
}
