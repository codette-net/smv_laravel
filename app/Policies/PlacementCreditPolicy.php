<?php

namespace App\Policies;

use App\Models\PlacementCredit;
use App\Models\User;

class PlacementCreditPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'redeem') {
            return null;
        }

        return $user->hasAnyRole(['super-admin', 'admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function view(User $user, PlacementCredit $placementCredit): bool
    {
        return $user->hasRole('editor');
    }

    public function redeem(User $user, PlacementCredit $placementCredit): bool
    {
        return $placementCredit->company()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PlacementCredit $placementCredit): bool
    {
        return false;
    }

    public function delete(User $user, PlacementCredit $placementCredit): bool
    {
        return false;
    }
}
