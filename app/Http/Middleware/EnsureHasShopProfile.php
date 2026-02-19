<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasShopProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            return $next($request);
        }

        if (! $request->user()?->shop) {
            return redirect()->route('shop.profile.edit')
                ->with('info', 'Please complete your shop profile to get started.');
        }

        return $next($request);
    }
}
