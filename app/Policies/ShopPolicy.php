<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'shop_owner', 'shop_manager']);
    }

    public function view(User $user, Shop $shop): bool
    {
        return $user->hasRole('admin') || $shop->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'shop_owner']);
    }

    public function update(User $user, Shop $shop): bool
    {
        return $user->hasRole('admin') || $shop->user_id === $user->id;
    }

    public function delete(User $user, Shop $shop): bool
    {
        return $user->hasRole('admin') || $shop->user_id === $user->id;
    }

    public function restore(User $user, Shop $shop): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Shop $shop): bool
    {
        return $user->hasRole('admin');
    }
}
