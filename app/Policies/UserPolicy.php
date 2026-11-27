<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('admin') && ! $model->hasRole('super-admin');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('admin')
            && ! $user->is($model)
            && ! $model->hasRole('super-admin');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('admin') && ! $model->hasRole('super-admin');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
