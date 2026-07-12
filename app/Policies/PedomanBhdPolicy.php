<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyAccess;

class PedomanBhdPolicy
{
    use AdminOnlyAccess;
}
