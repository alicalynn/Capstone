<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->disabled_at) {
            return response()->json([
                'message' => 'Your account has been disabled. Please contact admin support.'
            ], 403);
        }

        return $next($request);
    }
}
