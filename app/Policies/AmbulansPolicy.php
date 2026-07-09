<?php

namespace App\Policies;

use App\Models\Ambulans;
use App\Models\User;

class AmbulansPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ambulans.view');
    }

    public function view(User $user, Ambulans $ambulans): bool
    {
        return $user->can('ambulans.view');
    }

    public function create(User $user): bool
    {
        return $user->can('ambulans.create');
    }

    public function update(User $user, Ambulans $ambulans): bool
    {
        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('admin_faskes')) {
            return $ambulans->faskes?->admin_id === $user->id;
        }
        return false;
    }

    public function delete(User $user, Ambulans $ambulans): bool
    {
        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('admin_faskes')) {
            return $ambulans->faskes?->admin_id === $user->id;
        }
        return false;
    }
}
