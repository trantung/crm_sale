<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->owner_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
