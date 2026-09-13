<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recibe los avisos de la política de seguridad de contenido (CSP).
 *
 * La política viaja en modo reporte: el navegador no bloquea nada, solo avisa
 * cuando la página hace algo que la política prohíbe. Hasta el 13/09/2026 el
 * encabezado no decía a dónde mandar esos avisos, así que cada navegador los
 * escribía en su propia consola y se perdían ahí. Estábamos "escuchando" sin
 * recibir un solo aviso, y sin esa lista no hay forma de saber qué se rompería
 * al pasar la política a modo bloqueo.
 *
 * Esta dirección la llama el navegador solo, sin sesión y sin token: así
 * funciona el mecanismo. Eso la deja abierta a que cualquiera la use para
 * llenar el disco, y de ahí los límites de abajo.
 */
class CspReporteController extends Controller
{
    /** Un aviso legítimo del navegador no llega ni a 4 KB. */
    private const TAMANO_MAXIMO = 8192;

    /**
     * Las extensiones del navegador inyectan sus propios scripts en la página
     * y disparan avisos que no son de Wings. Son la mayor fuente de ruido de
     * cualquier CSP y no se pueden arreglar desde acá.
     */
    private const ORIGENES_AJENOS = [
        'chrome-extension', 'moz-extension', 'safari-extension',
        'safari-web-extension', 'webkit-masked-url',
    ];

    public function __invoke(Request $request): Response
    {
        // Siempre 204: el navegador no hace nada con la respuesta, y contestar
        // distinto según el caso le diría a un curioso qué acepta y qué no.
        $vacia = response()->noContent();

        if (strlen((string) $request->getContent()) > self::TAMANO_MAXIMO) {
            return $vacia;
        }

        $reporte = $request->json('csp-report');
        if (! is_array($reporte)) {
            return $vacia;
        }

        $bloqueado = (string) ($reporte['blocked-uri'] ?? '');
        foreach (self::ORIGENES_AJENOS as $esquema) {
            if (str_starts_with($bloqueado, $esquema)) {
                return $vacia;
            }
        }

        // Se guardan los campos que sirven para arreglar el problema y nada
        // más. El aviso completo trae recortes de la página, que pueden
        // contener datos de un alumno.
        Log::channel('csp')->info('violacion', [
            'directiva' => $this->recortar($reporte['effective-directive'] ?? $reporte['violated-directive'] ?? ''),
            'bloqueado' => $this->recortar($bloqueado),
            'pagina'    => $this->recortar($reporte['document-uri'] ?? ''),
            'archivo'   => $this->recortar($reporte['source-file'] ?? ''),
            'linea'     => (int) ($reporte['line-number'] ?? 0),
        ]);

        return $vacia;
    }

    private function recortar(mixed $valor): string
    {
        return mb_substr(trim((string) $valor), 0, 300);
    }
}
