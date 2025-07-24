<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function ban(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function unban(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function designate(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}
