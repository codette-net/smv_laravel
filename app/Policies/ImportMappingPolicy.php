<?php

namespace App\Policies;

use App\Models\ImportMapping;
use App\Models\User;

class ImportMappingPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function view(User $user, ImportMapping $mapping): bool
    {
        return $user->hasRole('editor');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function update(User $user, ImportMapping $mapping): bool
    {
        return $user->hasRole('editor');
    }

    public function delete(User $user, ImportMapping $mapping): bool
    {
        return false;
    }
}
