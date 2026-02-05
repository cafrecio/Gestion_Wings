<?php

namespace App\Services;

use App\Models\Liquidacion;
use App\Models\Pago;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReciboService
{
    /**
     * Directorio base para almacenar recibos
     */
    private const STORAGE_PATH = 'recibos';

    /**
     * Generar o recuperar recibo de pago de cuota.
     *
     * @param int $pagoId
     * @param bool $forceRegenerate Si true, regenera aunque exista el archivo
     * @return string Ruta del archivo PDF en storage
     * @throws \Exception
     */
    public function generarReciboCuota(int $pagoId, bool $forceRegenerate = false): string
    {
        $rutaRelativa = $this->obtenerRutaReciboCuota($pagoId);
        $rutaCompleta = storage_path('app/' . $rutaRelativa);

        // Si existe y no forzamos regeneración, retornar
        if (!$forceRegenerate && Storage::exists($rutaRelativa)) {
            return $rutaRelativa;
        }

        // Cargar pago con relaciones necesarias
        $pago = Pago::with([
            'alumno.deporte',
            'deudasCuota',
            'pagosDeuda.deudaCuota',
        ])->findOrFail($pagoId);

        // Buscar el tipo de caja desde movimientos relacionados
        $tipoCaja = $this->obtenerTipoCajaPago($pago);

        // Preparar datos para la vista
        $data = [
            'numero_recibo' => "CUOTA-{$pagoId}",
            'fecha_emision' => Carbon::now('America/Argentina/Buenos_Aires'),
            'fecha_pago' => $pago->fecha_pago,
            'alumno' => [
                'nombre' => trim(($pago->alumno->nombre ?? '') . ' ' . ($pago->alumno->apellido ?? '')),
                'dni' => $pago->alumno->dni ?? 'N/D',
                'deporte' => $pago->alumno->deporte->nombre ?? 'N/D',
            ],
            'periodos' => $this->obtenerPeriodosImputados($pago),
            'monto_total' => $pago->monto_final,
            'medio_cobro' => [
                'tipo_caja' => $tipoCaja['nombre'] ?? 'N/D',
                'origen' => $tipoCaja['origen'] ?? 'N/D', // 'Operativo' o 'Admin'
            ],
            'observaciones' => $pago->observaciones,
        ];

        // Generar PDF
        $pdf = Pdf::loadView('pdfs.recibo-cuota', $data);
        $pdf->setPaper('A5', 'portrait');

        // Asegurar que el directorio existe
        $this->asegurarDirectorio();

        // Guardar PDF
        Storage::put($rutaRelativa, $pdf->output());

        return $rutaRelativa;
    }

    /**
     * Generar o recuperar recibo de pago de liquidación.
     *
     * @param int $liquidacionId
     * @param bool $forceRegenerate Si true, regenera aunque exista el archivo
     * @return string Ruta del archivo PDF en storage
     * @throws \Exception
     */
    public function generarReciboLiquidacion(int $liquidacionId, bool $forceRegenerate = false): string
    {
        $rutaRelativa = $this->obtenerRutaReciboLiquidacion($liquidacionId);
        $rutaCompleta = storage_path('app/' . $rutaRelativa);

        // Si existe y no forzamos regeneración, retornar
        if (!$forceRegenerate && Storage::exists($rutaRelativa)) {
            return $rutaRelativa;
        }

        // Cargar liquidación con relaciones necesarias
        $liquidacion = Liquidacion::with([
            'profesor.deporte',
            'pagadaTipoCaja',
            'pagadaSubrubro',
        ])->findOrFail($liquidacionId);

        // Validar que esté pagada
        if (!$liquidacion->estaPagada()) {
            throw new \Exception("La liquidación #{$liquidacionId} no está pagada. No se puede generar recibo.");
        }

        // Preparar datos para la vista
        $data = [
            'numero_recibo' => "LIQ-{$liquidacionId}",
            'fecha_emision' => Carbon::now('America/Argentina/Buenos_Aires'),
            'fecha_pago' => $liquidacion->pagada_fecha,
            'profesor' => [
                'nombre' => $liquidacion->profesor->nombre_completo ?? $liquidacion->profesor->nombre ?? 'N/D',
                'deporte' => $liquidacion->profesor->deporte->nombre ?? 'N/D',
                'tipo_liquidacion' => $liquidacion->tipo,
            ],
            'periodo' => [
                'mes' => $liquidacion->mes,
                'anio' => $liquidacion->anio,
                'texto' => $this->formatearMesAnio($liquidacion->mes, $liquidacion->anio),
            ],
            'total_liquidado' => $liquidacion->total_calculado,
            'medio_pago' => [
                'tipo_caja' => $liquidacion->pagadaTipoCaja->nombre ?? 'N/D',
                'subrubro' => $liquidacion->pagadaSubrubro->nombre ?? 'N/D',
            ],
            'observaciones' => null, // Liquidación no tiene campo observaciones en pago
        ];

        // Generar PDF
        $pdf = Pdf::loadView('pdfs.recibo-liquidacion', $data);
        $pdf->setPaper('A5', 'portrait');

        // Asegurar que el directorio existe
        $this->asegurarDirectorio();

        // Guardar PDF
        Storage::put($rutaRelativa, $pdf->output());

        return $rutaRelativa;
    }

    /**
     * Obtener ruta determinística para recibo de cuota.
     */
    public function obtenerRutaReciboCuota(int $pagoId): string
    {
        return self::STORAGE_PATH . "/recibo-cuota-{$pagoId}.pdf";
    }

    /**
     * Obtener ruta determinística para recibo de liquidación.
     */
    public function obtenerRutaReciboLiquidacion(int $liquidacionId): string
    {
        return self::STORAGE_PATH . "/recibo-liquidacion-{$liquidacionId}.pdf";
    }

    /**
     * Verificar si existe el recibo de cuota.
     */
    public function existeReciboCuota(int $pagoId): bool
    {
        return Storage::exists($this->obtenerRutaReciboCuota($pagoId));
    }

    /**
     * Verificar si existe el recibo de liquidación.
     */
    public function existeReciboLiquidacion(int $liquidacionId): bool
    {
        return Storage::exists($this->obtenerRutaReciboLiquidacion($liquidacionId));
    }

    /**
     * Eliminar recibo de cuota (para regeneración).
     */
    public function eliminarReciboCuota(int $pagoId): bool
    {
        $ruta = $this->obtenerRutaReciboCuota($pagoId);
        if (Storage::exists($ruta)) {
            return Storage::delete($ruta);
        }
        return true;
    }

    /**
     * Eliminar recibo de liquidación (para regeneración).
     */
    public function eliminarReciboLiquidacion(int $liquidacionId): bool
    {
        $ruta = $this->obtenerRutaReciboLiquidacion($liquidacionId);
        if (Storage::exists($ruta)) {
            return Storage::delete($ruta);
        }
        return true;
    }

    /**
     * Intentar generar recibo de cuota de forma segura (no lanza excepción).
     * Usado para enganche automático post-transacción.
     *
     * @param int $pagoId
     * @return bool True si se generó correctamente
     */
    public function intentarGenerarReciboCuota(int $pagoId): bool
    {
        try {
            $this->generarReciboCuota($pagoId);
            return true;
        } catch (\Throwable $e) {
            Log::error("Error generando recibo de cuota para pago #{$pagoId}: " . $e->getMessage(), [
                'pago_id' => $pagoId,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Intentar generar recibo de liquidación de forma segura (no lanza excepción).
     * Usado para enganche automático post-transacción.
     *
     * @param int $liquidacionId
     * @return bool True si se generó correctamente
     */
    public function intentarGenerarReciboLiquidacion(int $liquidacionId): bool
    {
        try {
            $this->generarReciboLiquidacion($liquidacionId);
            return true;
        } catch (\Throwable $e) {
            Log::error("Error generando recibo de liquidación #{$liquidacionId}: " . $e->getMessage(), [
                'liquidacion_id' => $liquidacionId,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Obtener los períodos imputados en el pago con sus montos.
     */
    private function obtenerPeriodosImputados(Pago $pago): array
    {
        $periodos = [];

        foreach ($pago->pagosDeuda as $pagoDeuda) {
            $deuda = $pagoDeuda->deudaCuota;
            $periodos[] = [
                'periodo' => $deuda->periodo,
                'periodo_texto' => $this->formatearPeriodo($deuda->periodo),
                'monto_aplicado' => $pagoDeuda->monto_aplicado,
            ];
        }

        return $periodos;
    }

    /**
     * Obtener el tipo de caja y origen del pago (operativo o admin).
     */
    private function obtenerTipoCajaPago(Pago $pago): array
    {
        // Buscar en MovimientoOperativo
        $movOperativo = \App\Models\MovimientoOperativo::whereHas('cajaOperativa', function ($q) use ($pago) {
            // Buscar por observaciones que contengan el ID del pago
            // o por fecha y monto similar
        })->where('observaciones', 'LIKE', "%Pago cuota alumno #{$pago->alumno_id}%")
          ->where('monto', $pago->monto_final)
          ->whereDate('fecha', $pago->fecha_pago)
          ->with('tipoCaja')
          ->first();

        if ($movOperativo && $movOperativo->tipoCaja) {
            return [
                'nombre' => $movOperativo->tipoCaja->nombre,
                'origen' => 'Operativo',
            ];
        }

        // Buscar en CashflowMovimiento
        $cashflow = \App\Models\CashflowMovimiento::where('referencia_tipo', 'PAGO_CUOTA')
            ->where('referencia_id', $pago->id)
            ->with('tipoCaja')
            ->first();

        if ($cashflow && $cashflow->tipoCaja) {
            return [
                'nombre' => $cashflow->tipoCaja->nombre,
                'origen' => 'Admin',
            ];
        }

        return [
            'nombre' => 'N/D',
            'origen' => 'N/D',
        ];
    }

    /**
     * Formatear período YYYY-MM a texto legible.
     */
    private function formatearPeriodo(string $periodo): string
    {
        $parts = explode('-', $periodo);
        if (count($parts) !== 2) {
            return $periodo;
        }

        return $this->formatearMesAnio((int) $parts[1], (int) $parts[0]);
    }

    /**
     * Formatear mes y año a texto legible.
     */
    private function formatearMesAnio(int $mes, int $anio): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return ($meses[$mes] ?? $mes) . ' ' . $anio;
    }

    /**
     * Asegurar que el directorio de recibos existe.
     */
    private function asegurarDirectorio(): void
    {
        $path = self::STORAGE_PATH;
        if (!Storage::exists($path)) {
            Storage::makeDirectory($path);
        }
    }
}
