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
        $middleware->api(prepend: ['throttle:api']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON responses for API routes when authentication fails
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        // Ensure all API exceptions return JSON with a clear message
        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }
            // Let Laravel handle validation errors (422 + errors array)
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return null;
            }
            $message = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException
                ? $e->getMessage()
                : (config('app.debug') ? $e->getMessage() : 'An error occurred. Please try again.');
            $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException
                ? $e->getStatusCode()
                : 500;

            return response()->json([
                'message' => $message ?: 'An error occurred. Please try again.',
            ], $status);
        });
    })->create();
