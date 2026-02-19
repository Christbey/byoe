<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasProviderProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            return $next($request);
        }

        if (! $request->user()?->provider) {
            return redirect()->route('provider.profile.edit')
                ->with('info', 'Please complete your provider profile to get started.');
        }

        return $next($request);
    }
}
