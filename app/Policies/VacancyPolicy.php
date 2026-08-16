<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacancy;

class VacancyPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function view(User $user, Vacancy $vacancy): bool
    {
        return $user->hasRole('editor');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function update(User $user, Vacancy $vacancy): bool
    {
        return $user->hasRole('editor');
    }

    public function delete(User $user, Vacancy $vacancy): bool
    {
        return false;
    }

    public function restore(User $user, Vacancy $vacancy): bool
    {
        return false;
    }

    public function forceDelete(User $user, Vacancy $vacancy): bool
    {
        return false;
    }
}
