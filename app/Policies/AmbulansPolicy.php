<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyAccess;

class AmbulansPolicy
{
    use AdminOnlyAccess;
}
