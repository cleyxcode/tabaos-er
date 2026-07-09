<?php

namespace App\Policies;

use App\Models\Relawan;
use App\Models\User;

class RelawanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('relawan.view');
    }

    public function view(User $user, Relawan $relawan): bool
    {
        return $user->can('relawan.view');
    }

    public function create(User $user): bool
    {
        return $user->can('relawan.create');
    }

    public function update(User $user, Relawan $relawan): bool
    {
        return $user->can('relawan.update');
    }

    public function delete(User $user, Relawan $relawan): bool
    {
        return $user->can('relawan.delete');
    }
}
