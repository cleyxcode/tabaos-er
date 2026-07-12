<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyAccess;

class FaskesPolicy
{
    use AdminOnlyAccess;
}
