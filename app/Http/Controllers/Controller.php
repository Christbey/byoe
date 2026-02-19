<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Resolve the shop for the current user.
     * Admins without a shop fall back to the first available shop.
     */
    protected function resolveShop(Request $request): ?Shop
    {
        return $request->user()->shop
            ?? ($request->user()->hasRole('admin') ? Shop::first() : null);
    }
}
