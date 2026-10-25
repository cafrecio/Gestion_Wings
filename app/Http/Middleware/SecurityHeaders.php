<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * `report-uri` es lo que hace que el modo reporte sirva para algo.
     *
     * Sin esa directiva el navegador escribe los avisos en su propia consola,
     * en la máquina de cada usuario, y nadie los ve nunca: la política quedaba
     * "en modo reporte" sin recolectar un solo reporte, y así no hay manera de
     * saber qué se rompería al pasarla a modo bloqueo.
     *
     * Se usa `report-uri` y no `report-to` porque el segundo necesita además el
     * encabezado `Reporting-Endpoints` y todavía no lo soportan todos los
     * navegadores. `report-uri` está marcado como obsoleto en la norma pero es
     * el que funciona hoy en todos lados.
     *
     * `static.cloudflareinsights.com` es el medidor de visitas que Cloudflare agrega
     * solo a todas las páginas; no está en nuestro código. Sin permitirlo generaba un
     * aviso por cada pantalla abierta: el 13/10/2026, 41 de los 66 avisos del día en
     * el sitio de prueba eran ese script, y tapaban cualquier aviso de verdad.
     * Sus mediciones las manda a `/cdn-cgi/rum`, en el mismo dominio.
     *
     * T80 (25/10/2026): pasa de modo reporte a modo bloqueo. Desde T73 no queda JavaScript
     * dentro de las vistas; en el sitio de prueba no hubo un solo aviso desde que T73 llegó
     * (21/10), con los días 4 a 6 recorriendo el sistema. Los estilos en línea siguen
     * permitidos ('unsafe-inline' en style-src): hay más de mil y no son código.
     * Se conserva report-uri: si algo queda bloqueado, el aviso llega igual.
     */
    private const CSP = "default-src 'self'; script-src 'self' https://static.cloudflareinsights.com; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' data: https:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; report-uri /csp-reporte";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Content-Security-Policy', self::CSP);

        return $response;
    }
}
