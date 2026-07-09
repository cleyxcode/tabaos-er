<?php

namespace App\Policies;

use App\Models\Faskes;
use App\Models\User;

class FaskesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('faskes.view');
    }

    public function view(User $user, Faskes $faskes): bool
    {
        return $user->can('faskes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('faskes.create');
    }

    public function update(User $user, Faskes $faskes): bool
    {
        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('admin_faskes')) return $faskes->admin_id === $user->id;
        return false;
    }

    public function delete(User $user, Faskes $faskes): bool
    {
        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('admin_faskes')) return $faskes->admin_id === $user->id;
        return false;
    }
}
