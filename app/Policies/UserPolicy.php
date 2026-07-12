<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AdminOnlyAccess;

class UserPolicy
{
    use AdminOnlyAccess;

    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->id !== $model->id;
    }
}
