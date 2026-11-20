<?php

namespace App\Policies;

use App\Enums\VacancyStatus;
use App\Models\User;
use App\Models\Vacancy;

class VacancyPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'duplicate') {
            return null;
        }

        return $user->hasAnyRole(['super-admin', 'admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function view(User $user, Vacancy $vacancy): bool
    {
        return $user->hasRole('editor') || $vacancy->company()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['editor', 'employer']) || $user->companies()->exists();
    }

    public function update(User $user, Vacancy $vacancy): bool
    {
        return $user->hasRole('editor') || (
            $vacancy->status === VacancyStatus::Draft
            && $vacancy->company()->where('user_id', $user->id)->exists()
        );
    }

    public function duplicate(User $user, Vacancy $vacancy): bool
    {
        return $vacancy->import_source_id !== null
            && $user->hasAnyRole(['super-admin', 'admin', 'editor']);
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
