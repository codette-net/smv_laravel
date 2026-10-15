<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasRole('editor');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasRole('editor');
    }

    public function delete(User $user, Category $category): bool
    {
        return false;
    }

    public function restore(User $user, Category $category): bool
    {
        return false;
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return false;
    }
}
