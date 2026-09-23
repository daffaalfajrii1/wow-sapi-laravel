<?php

namespace App\Policies;

use App\Models\Cattle;
use App\Models\User;

class CattlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPeternak();
    }

    public function view(User $user, Cattle $cattle): bool
    {
        return $cattle->belongsToUser($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isPeternak();
    }

    public function update(User $user, Cattle $cattle): bool
    {
        return $cattle->belongsToUser($user);
    }

    public function delete(User $user, Cattle $cattle): bool
    {
        return $cattle->belongsToUser($user);
    }

    public function examine(User $user, Cattle $cattle): bool
    {
        return $cattle->belongsToUser($user) && $cattle->status === 'active';
    }

    public function recordMortality(User $user, Cattle $cattle): bool
    {
        return $cattle->belongsToUser($user) && $cattle->status !== 'dead';
    }
}
