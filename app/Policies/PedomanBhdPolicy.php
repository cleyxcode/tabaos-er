<?php

namespace App\Policies;

use App\Models\PedomanBhd;
use App\Models\User;

class PedomanBhdPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pedoman.view');
    }

    public function view(User $user, PedomanBhd $pedoman): bool
    {
        return $user->can('pedoman.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pedoman.create');
    }

    public function update(User $user, PedomanBhd $pedoman): bool
    {
        return $user->can('pedoman.update');
    }

    public function delete(User $user, PedomanBhd $pedoman): bool
    {
        return $user->can('pedoman.delete');
    }
}
