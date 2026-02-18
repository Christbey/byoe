<?php

namespace App\Actions;

use App\Models\Shop;
use App\Models\User;

class CreateShopAction
{
    /**
     * Execute the action.
     *
     * @param array $validated The validated shop data
     * @param User $user The user who owns the shop
     * @return Shop The created shop
     */
    public function __invoke(array $validated, User $user): Shop
    {
        return Shop::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'operating_hours' => $validated['operating_hours'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);
    }
}
