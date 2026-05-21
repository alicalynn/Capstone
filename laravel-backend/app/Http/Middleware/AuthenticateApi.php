<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class AuthenticateApi extends Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        // For API requests, don't redirect - let the guard handle it
        if ($request->expectsJson() || $request->is('api/*')) {
            return parent::handle($request, $next, ...$guards);
        }
        
        return parent::handle($request, $next, ...$guards);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Don't redirect API requests - return null to let them fail naturally with 401
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        return '/login';
    }
}
