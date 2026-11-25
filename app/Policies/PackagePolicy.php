<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;

class PackagePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($ability, ['create', 'delete', 'restore', 'forceDelete'], true)) {
            return null;
        }

        return $user->hasAnyRole(['super-admin', 'admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function view(User $user, Package $package): bool
    {
        return $user->hasRole('editor');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Package $package): bool
    {
        return false;
    }

    public function delete(User $user, Package $package): bool
    {
        return false;
    }

    public function restore(User $user, Package $package): bool
    {
        return false;
    }

    public function forceDelete(User $user, Package $package): bool
    {
        return false;
    }
}
