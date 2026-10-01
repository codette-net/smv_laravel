<?php

namespace App\Policies;

use App\Models\Import;
use App\Models\User;

class ImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function view(User $user, Import $import): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Import $import): bool
    {
        return false;
    }

    public function delete(User $user, Import $import): bool
    {
        return false;
    }
}
