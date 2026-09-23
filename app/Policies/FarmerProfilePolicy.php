<?php

namespace App\Policies;

use App\Models\FarmerProfile;
use App\Models\User;

class FarmerProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, FarmerProfile $profile): bool
    {
        return $user->isAdmin() || $profile->user_id === $user->id;
    }

    public function update(User $user, FarmerProfile $profile): bool
    {
        return $user->isAdmin() || $profile->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }
}
