<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add CORS middleware globally for API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\CorsMiddleware::class,
        ]);
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'auth.admin' => \App\Http\Middleware\AdminAuthMiddleware::class,
            'karenderia.approved' => \App\Http\Middleware\CheckKarenderiaApproval::class,
            'supplier.verified' => \App\Http\Middleware\SupplierVerifiedMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Add CORS headers to all responses including errors
        $exceptions->respond(function (\Illuminate\Http\Response $response, \Throwable $e) {
            try {
                $request = app(\Illuminate\Http\Request::class);
                $origin = $request?->header('Origin');
            } catch (\Exception $ex) {
                $origin = null;
            }
            
            $allowedOrigins = [
                'http://localhost:8100',
                'http://127.0.0.1:8100',
                'http://192.168.1.17:8100',
                'http://192.168.0.117:8100',
                'http://192.168.100.136:8100',
            ];

            $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : '*';
            
            return $response
                ->header('Access-Control-Allow-Origin', $allowedOrigin)
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
                ->header('Access-Control-Expose-Headers', 'Content-Length');
        });
    })->create();
