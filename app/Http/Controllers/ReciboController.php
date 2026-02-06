<?php

namespace App\Http\Controllers;

use App\Models\Liquidacion;
use App\Models\Pago;
use App\Services\ReciboService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReciboController extends Controller
{
    private ReciboService $reciboService;

    public function __construct(ReciboService $reciboService)
    {
        $this->reciboService = $reciboService;
    }

    /**
     * GET /api/recibos/cuota/{pagoId}
     *
     * Descarga el recibo PDF de un pago de cuota.
     * Si no existe, lo genera automáticamente.
     *
     * Query params:
     * - regenerar=1 : Fuerza regeneración del PDF
     * - inline=1    : Muestra en navegador en lugar de descargar
     *
     * @param Request $request
     * @param int $pagoId
     * @return Response
     */
    public function cuota(Request $request, int $pagoId)
    {
        // Validar que el pago existe
        $pago = Pago::find($pagoId);
        if (!$pago) {
            return response()->json([
                'error' => 'Pago no encontrado',
                'message' => "No existe un pago con ID {$pagoId}",
            ], 404);
        }

        // Permisos: auth:sanctum en ruta (ADMIN y OPERATIVO pueden acceder)

        try {
            $forceRegenerate = $request->boolean('regenerar', false);
            $rutaRelativa = $this->reciboService->generarReciboCuota($pagoId, $forceRegenerate);

            if (!Storage::exists($rutaRelativa)) {
                return response()->json([
                    'error' => 'Error al generar recibo',
                    'message' => 'El archivo PDF no pudo ser creado',
                ], 500);
            }

            $contenido = Storage::get($rutaRelativa);
            $nombreArchivo = "recibo-cuota-{$pagoId}.pdf";

            $disposition = $request->boolean('inline', false)
                ? 'inline'
                : 'attachment';

            return response($contenido, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$nombreArchivo}\"",
                'Content-Length' => strlen($contenido),
                'Cache-Control' => 'private, max-age=3600',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al generar recibo',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/recibos/liquidacion/{liquidacionId}
     *
     * Descarga el recibo PDF de una liquidación pagada.
     * Si no existe, lo genera automáticamente.
     *
     * Query params:
     * - regenerar=1 : Fuerza regeneración del PDF
     * - inline=1    : Muestra en navegador en lugar de descargar
     *
     * @param Request $request
     * @param int $liquidacionId
     * @return Response
     */
    public function liquidacion(Request $request, int $liquidacionId)
    {
        // Validar que la liquidación existe
        $liquidacion = Liquidacion::find($liquidacionId);
        if (!$liquidacion) {
            return response()->json([
                'error' => 'Liquidación no encontrada',
                'message' => "No existe una liquidación con ID {$liquidacionId}",
            ], 404);
        }

        // Validar que esté pagada
        if (!$liquidacion->estaPagada()) {
            return response()->json([
                'error' => 'Liquidación no pagada',
                'message' => 'Solo se pueden generar recibos de liquidaciones que han sido pagadas',
            ], 400);
        }

        // Permisos: auth:sanctum + ensure.admin en ruta (solo ADMIN)

        try {
            $forceRegenerate = $request->boolean('regenerar', false);
            $rutaRelativa = $this->reciboService->generarReciboLiquidacion($liquidacionId, $forceRegenerate);

            if (!Storage::exists($rutaRelativa)) {
                return response()->json([
                    'error' => 'Error al generar recibo',
                    'message' => 'El archivo PDF no pudo ser creado',
                ], 500);
            }

            $contenido = Storage::get($rutaRelativa);
            $nombreArchivo = "recibo-liquidacion-{$liquidacionId}.pdf";

            $disposition = $request->boolean('inline', false)
                ? 'inline'
                : 'attachment';

            return response($contenido, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$nombreArchivo}\"",
                'Content-Length' => strlen($contenido),
                'Cache-Control' => 'private, max-age=3600',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al generar recibo',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/recibos/cuota/{pagoId}/info
     *
     * Obtiene información sobre el recibo sin descargarlo.
     *
     * @param int $pagoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function infoCuota(int $pagoId)
    {
        $pago = Pago::find($pagoId);
        if (!$pago) {
            return response()->json([
                'error' => 'Pago no encontrado',
            ], 404);
        }

        $existe = $this->reciboService->existeReciboCuota($pagoId);

        return response()->json([
            'pago_id' => $pagoId,
            'numero_recibo' => "CUOTA-{$pagoId}",
            'existe_pdf' => $existe,
            'url_descarga' => $existe ? url("/api/recibos/cuota/{$pagoId}") : null,
            'url_inline' => $existe ? url("/api/recibos/cuota/{$pagoId}?inline=1") : null,
        ]);
    }

    /**
     * GET /api/recibos/liquidacion/{liquidacionId}/info
     *
     * Obtiene información sobre el recibo sin descargarlo.
     *
     * @param int $liquidacionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function infoLiquidacion(int $liquidacionId)
    {
        $liquidacion = Liquidacion::find($liquidacionId);
        if (!$liquidacion) {
            return response()->json([
                'error' => 'Liquidación no encontrada',
            ], 404);
        }

        $existe = $this->reciboService->existeReciboLiquidacion($liquidacionId);

        return response()->json([
            'liquidacion_id' => $liquidacionId,
            'numero_recibo' => "LIQ-{$liquidacionId}",
            'esta_pagada' => $liquidacion->estaPagada(),
            'existe_pdf' => $existe,
            'url_descarga' => ($existe && $liquidacion->estaPagada()) ? url("/api/recibos/liquidacion/{$liquidacionId}") : null,
            'url_inline' => ($existe && $liquidacion->estaPagada()) ? url("/api/recibos/liquidacion/{$liquidacionId}?inline=1") : null,
        ]);
    }
}
