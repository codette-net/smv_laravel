<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'pay') {
            return null;
        }

        return $user->hasAnyRole(['super-admin', 'admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasRole('editor') || (
            $order->user_id === $user->id
            && $order->company()->where('user_id', $user->id)->exists()
        );
    }

    public function pay(User $user, Order $order): bool
    {
        return $order->user_id === $user->id
            && $order->company()->where('user_id', $user->id)->exists();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->hasRole('editor') || $user->getAllPermissions()->contains('name', 'orders.cancel');
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->hasRole('editor') || $user->getAllPermissions()->contains('name', 'orders.refund');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Order $order): bool
    {
        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }
}
