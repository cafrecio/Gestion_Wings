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
        // api: __DIR__.'/../routes/api.php', // DESHABILITADA (fix S2, 2026-07-13)
        // routes/api.php quedó expuesta sin control de rol (alumnos, pagos,
        // liquidaciones editables por CUALQUIER usuario autenticado, incluso
        // PROFESOR) y sin consumidor real: nada en resources/ ni config/cors.php
        // le pega. Ver docs/07-evaluacion/seguridad/REPORTE-SEGURIDAD.md S2.0.
        // Para reactivarla: descomentar la línea de arriba y antes cerrar cada
        // grupo de rutas con el middleware de rol correcto (S2), agregar
        // throttle al login (S4) y auditoría de ownership en recibos (S3).
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'bloqueo.caja.vieja' => \App\Http\Middleware\BloqueoCajaViejaOperativo::class,
            'ensure.admin'      => \App\Http\Middleware\EnsureAdmin::class,
            'ensure.admin.web'  => \App\Http\Middleware\EnsureAdminWeb::class,
            'ensure.profesor.web' => \App\Http\Middleware\EnsureProfesorWeb::class,
            'reject.profesor.web' => \App\Http\Middleware\RejectProfesorWeb::class,
            'ensure.active.web' => \App\Http\Middleware\EnsureActiveUserWeb::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->appendToGroup('web', ['security.headers']);

        // El navegador manda los avisos de CSP por su cuenta, sin sesión y sin
        // token: exigirle CSRF los rechazaría a todos. No recibe datos del
        // usuario ni cambia nada del sistema, solo escribe una línea de log.
        $middleware->validateCsrfTokens(except: ['csp-reporte']);

        // El sitio va a quedar detrás de Cloudflare, que actúa de intermediario:
        // la gente le habla a Cloudflare y Cloudflare le habla al servidor.
        //
        // Sin esto, la aplicación ve SIEMPRE la dirección de Cloudflare y nunca la
        // del visitante. Dos consecuencias concretas:
        //
        //  1. El límite de intentos del login (throttle:5,1) cuenta por dirección.
        //     Con todos compartiendo una, cinco intentos fallidos de cualquiera
        //     dejan afuera al club entero.
        //  2. La aplicación cree que la conexión no está cifrada, porque el cifrado
        //     lo termina Cloudflare, y arma las direcciones con http://: enlaces de
        //     recuperación rotos y posibles bucles de redirección.
        //
        // Se confían SOLO los rangos publicados por Cloudflare, no cualquiera. Si se
        // confiara en todos, quien alcance el servidor por su IP directa podría
        // mentir sobre quién es.
        //
        // El complemento de esto ya está hecho en el servidor (06/09/2026): los
        // puertos 80 y 443 salieron de TCP_IN en CSF y solo se reabren para estos
        // mismos rangos, más el cierre de IPv6 en csfpost.sh. Verificado: por la IP
        // directa el sitio no responde. Si alguna vez esa restricción se sacara,
        // esta lista vuelve a ser una defensa a medias.
        // Detalle en el proyecto de plataforma: VPS/ESTADO-SERVIDOR.md.
        //
        // Rangos traídos de cloudflare.com/ips-v4 e ips-v6 el 06/09/2026.
        // Cambian muy de vez en cuando; si Cloudflare suma uno y no está acá, la
        // aplicación vuelve a ver la dirección del intermediario para ese tramo.
        $middleware->trustProxies(at: [
            '173.245.48.0/20',  '103.21.244.0/22',  '103.22.200.0/22',
            '103.31.4.0/22',    '141.101.64.0/18',  '108.162.192.0/18',
            '190.93.240.0/20',  '188.114.96.0/20',  '197.234.240.0/22',
            '198.41.128.0/17',  '162.158.0.0/15',   '104.16.0.0/13',
            '104.24.0.0/14',    '172.64.0.0/13',    '131.0.72.0/22',
            '2400:cb00::/32',   '2606:4700::/32',   '2803:f800::/32',
            '2405:b500::/32',   '2405:8100::/32',   '2a06:98c0::/29',
            '2c0f:f248::/32',
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
