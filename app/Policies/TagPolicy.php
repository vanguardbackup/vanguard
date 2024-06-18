<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        //
    }

    public function create(User $user): bool
    {
        //
    }

    public function view(User $user, Tag $tag): Response
    {
        return $user->id === $tag->user_id
            ? Response::allow()
            : Response::deny('You do not own this tag.');
    }

    public function update(User $user, Tag $tag): Response
    {
        return $user->id === $tag->user_id
            ? Response::allow()
            : Response::deny('You do not own this tag.');
    }

    public function forceDelete(User $user, Tag $tag): Response
    {
        return $user->id === $tag->user_id
            ? Response::allow()
            : Response::deny('You do not own this tag.');
    }
}
