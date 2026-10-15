<?php

namespace App\Policies;

use App\Models\ImportSource;
use App\Models\User;

class ImportSourcePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function view(User $user, ImportSource $source): bool
    {
        return $user->hasRole('editor');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function update(User $user, ImportSource $source): bool
    {
        return $user->hasRole('editor');
    }

    public function delete(User $user, ImportSource $source): bool
    {
        return false;
    }

    public function restore(User $user, ImportSource $source): bool
    {
        return false;
    }

    public function forceDelete(User $user, ImportSource $source): bool
    {
        return false;
    }

    public function approve(User $user, ?ImportSource $source = null): bool
    {
        return false;
    }
}
