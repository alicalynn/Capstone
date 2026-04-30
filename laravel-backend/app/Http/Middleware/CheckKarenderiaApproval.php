<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckKarenderiaApproval
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Only check karenderia owners
        if ($user && $user->role === 'karenderia_owner') {
            $karenderia = $user->karenderia;
            
            if (!$karenderia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karenderia application not found',
                    'status' => 'no_application'
                ], 403);
            }
            
            if ($karenderia->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your karenderia application is still pending admin approval. You cannot access karenderia features until approved.',
                    'status' => 'pending_approval',
                    'application_details' => [
                        'business_name' => $karenderia->business_name,
                        'submitted_at' => $karenderia->created_at->format('M d, Y'),
                        'status' => 'pending'
                    ]
                ], 403);
            }
            
            if ($karenderia->status === 'rejected') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your karenderia application was rejected. You cannot access karenderia features.',
                    'status' => 'rejected',
                    'application_details' => [
                        'business_name' => $karenderia->business_name,
                        'rejected_at' => $karenderia->rejected_at ? $karenderia->rejected_at->format('M d, Y') : null,
                        'rejection_reason' => $karenderia->rejection_reason,
                        'status' => 'rejected'
                    ]
                ], 403);
            }
            
            // Approved and active karenderias can access protected owner features.
            if (!in_array($karenderia->status, ['approved', 'active'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karenderia must be approved or active to access this feature',
                    'status' => 'not_approved'
                ], 403);
            }
        }
        
        return $next($request);
    }
}
