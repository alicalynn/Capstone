<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');

        // Allow any origin for development/testing
        $allowedOrigin = $origin ?? '*';

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $allowedOrigin)
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, X-CSRF-Token')
                ->header('Access-Control-Max-Age', '86400');
        }

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            // Ensure CORS headers are added even on exception
            $response = response()->json([
                'message' => $e->getMessage(),
                'error' => get_class($e)
            ], 500);
        }

        foreach ([
            'Access-Control-Allow-Origin' => $allowedOrigin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, X-CSRF-Token',
            'Access-Control-Expose-Headers' => 'Content-Length, X-JSON-Response',
        ] as $header => $value) {
            $response->headers->set($header, $value);
        }

        return $response;
    }
}

