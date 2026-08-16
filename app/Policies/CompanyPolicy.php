<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->hasRole('editor');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasRole('editor');
    }

    public function delete(User $user, Company $company): bool
    {
        return false;
    }

    public function restore(User $user, Company $company): bool
    {
        return false;
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return false;
    }
}
