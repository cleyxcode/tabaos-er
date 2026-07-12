<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyAccess;

class RelawanPolicy
{
    use AdminOnlyAccess;
}
