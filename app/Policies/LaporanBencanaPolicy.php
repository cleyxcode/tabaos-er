<?php

namespace App\Policies;

use App\Models\LaporanBencana;
use App\Models\User;
use App\Policies\Concerns\AdminOnlyAccess;

class LaporanBencanaPolicy
{
    use AdminOnlyAccess;

    public function verify(User $user, LaporanBencana $laporan): bool
    {
        return true;
    }
}
