<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyAccess;

class PenugasanPolicy
{
    use AdminOnlyAccess;
}
