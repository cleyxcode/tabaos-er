<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ZonaRawanBencana;

class ZonaRawanBencanaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('zona.view');
    }

    public function view(User $user, ZonaRawanBencana $zona): bool
    {
        return $user->can('zona.view');
    }

    public function create(User $user): bool
    {
        return $user->can('zona.create');
    }

    public function update(User $user, ZonaRawanBencana $zona): bool
    {
        return $user->can('zona.update');
    }

    public function delete(User $user, ZonaRawanBencana $zona): bool
    {
        return $user->can('zona.delete');
    }
}
