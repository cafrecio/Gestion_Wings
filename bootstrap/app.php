<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'bloqueo.caja.vieja' => \App\Http\Middleware\BloqueoCajaViejaOperativo::class,
            'ensure.admin'      => \App\Http\Middleware\EnsureAdmin::class,
            'ensure.admin.web'  => \App\Http\Middleware\EnsureAdminWeb::class,
            'ensure.profesor.web' => \App\Http\Middleware\EnsureProfesorWeb::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 CSRF expirado → login. TokenMismatchException es convertida a
        // HttpException(419) por prepareException() antes de llegar aquí.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && !$request->expectsJson()) {
                return redirect()->route('login')
                    ->with('error', 'Tu sesión expiró. Iniciá sesión nuevamente.');
            }
        });

        // 401 UNAUTHENTICATED uniforme para API
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                    ],
                ], 401);
            }
        });

        // 403 Authorization errors uniforme para API
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => $e->getMessage(),
                    ],
                ], 403);
            }
        });
    })->create();
