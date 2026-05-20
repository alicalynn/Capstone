<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * List of allowed origins
     */
    protected $allowedOrigins = [
        'http://localhost:8100',
        'http://127.0.0.1:8100',
        'http://192.168.1.17:8100',
        'http://192.168.0.117:8100',
        'http://192.168.100.136:8100',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');

        // Check if origin is allowed
        $allowedOrigin = $this->isOriginAllowed($origin) ? $origin : '*';

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
        } catch (\Exception $e) {
            // Ensure CORS headers are added even on exception
            $response = response()->json([
                'message' => $e->getMessage(),
                'error' => get_class($e)
            ], 500);
        }

        // Add CORS headers to response
        $response->header('Access-Control-Allow-Origin', $allowedOrigin)
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, X-CSRF-Token')
                ->header('Access-Control-Expose-Headers', 'Content-Length, X-JSON-Response');

        return $response;
    }

    /**
     * Check if origin is allowed
     */
    protected function isOriginAllowed($origin): bool
    {
        if (!$origin) {
            return false;
        }

        return in_array($origin, $this->allowedOrigins);
    }
}
