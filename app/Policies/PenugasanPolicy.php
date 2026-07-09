<?php

namespace App\Policies;

use App\Models\Penugasan;
use App\Models\User;

class PenugasanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('penugasan.view');
    }

    public function view(User $user, Penugasan $penugasan): bool
    {
        return $user->can('penugasan.view');
    }

    public function create(User $user): bool
    {
        return $user->can('penugasan.create');
    }

    public function update(User $user, Penugasan $penugasan): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('koordinator_relawan')) return true;
        // Petugas penanganan hanya boleh update penugasan miliknya sendiri
        if ($user->hasRole('petugas_penanganan')) {
            return $penugasan->petugas_id === $user->id && $user->can('penugasan.update');
        }
        return false;
    }

    public function delete(User $user, Penugasan $penugasan): bool
    {
        return $user->can('penugasan.delete');
    }
}
