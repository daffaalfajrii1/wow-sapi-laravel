<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vaccine;

class VaccinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPeternak();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Vaccine $vaccine): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Vaccine $vaccine): bool
    {
        return $user->isAdmin();
    }
}
