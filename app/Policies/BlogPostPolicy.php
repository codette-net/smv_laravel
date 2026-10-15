<?php

namespace App\Policies;

use App\Models\BlogPost;
use App\Models\User;

class BlogPostPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function view(User $user, BlogPost $blogPost): bool
    {
        return $user->hasRole('editor');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function update(User $user, BlogPost $blogPost): bool
    {
        return $user->hasRole('editor');
    }

    public function delete(User $user, BlogPost $blogPost): bool
    {
        return false;
    }

    public function restore(User $user, BlogPost $blogPost): bool
    {
        return false;
    }

    public function forceDelete(User $user, BlogPost $blogPost): bool
    {
        return false;
    }
}
