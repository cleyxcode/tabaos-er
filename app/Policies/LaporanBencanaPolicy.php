<?php

namespace App\Policies;

use App\Models\LaporanBencana;
use App\Models\User;

class LaporanBencanaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('laporan.view');
    }

    public function view(User $user, LaporanBencana $laporan): bool
    {
        return $user->can('laporan.view');
    }

    public function create(User $user): bool
    {
        return $user->can('laporan.create');
    }

    public function update(User $user, LaporanBencana $laporan): bool
    {
        return $user->can('laporan.update');
    }

    public function delete(User $user, LaporanBencana $laporan): bool
    {
        return $user->can('laporan.delete');
    }

    public function verify(User $user, LaporanBencana $laporan): bool
    {
        return $user->can('laporan.verify');
    }
}
