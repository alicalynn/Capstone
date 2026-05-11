<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Karenderia;

class KarenderiaApprovedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is a karenderia owner
        if ($user->role !== 'karenderia_owner') {
            return response()->json(['message' => 'Only karenderia owners can access this resource'], 403);
        }

        // Check if they have an approved karenderia
        $karenderia = Karenderia::where('owner_id', $user->id)
            ->where('status', 'approved')
            ->orWhere('status', 'active')
            ->first();

        if (!$karenderia) {
            return response()->json([
                'message' => 'Your karenderia must be approved before you can post ingredient requests',
                'status' => 'unapproved'
            ], 403);
        }

        return $next($request);
    }
}
