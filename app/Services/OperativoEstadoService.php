<?php

namespace App\Services;

use App\Models\CajaOperativa;
use Carbon\Carbon;

class OperativoEstadoService
{
    private const TZ_ARGENTINA = 'America/Argentina/Buenos_Aires';

    /**
     * Obtener el estado operativo del día para un usuario
     *
     * @param int $userId
     * @param Carbon|null $hoyAr Fecha en TZ Argentina (default: ahora)
     * @return array
     */
    public function obtenerEstadoHoy(int $userId, ?Carbon $hoyAr = null): array
    {
        $hoyAr = $hoyAr ?? Carbon::now(self::TZ_ARGENTINA);
        $fechaAr = $hoyAr->format('Y-m-d');

        // Rango del día en UTC para queries
        [$inicioHoyUtc, $finHoyUtc] = $this->rangoDelDiaEnUtc($hoyAr);

        // 1. Buscar caja ABIERTA actual (de hoy)
        $cajaActual = CajaOperativa::where('usuario_operativo_id', $userId)
            ->where('estado', 'ABIERTA')
            ->whereBetween('apertura_at', [$inicioHoyUtc, $finHoyUtc])
            ->first();

        // 2. Buscar última caja de hoy (si no hay abierta)
        $ultimaCajaHoy = null;
        if (!$cajaActual) {
            $ultimaCajaHoy = CajaOperativa::where('usuario_operativo_id', $userId)
                ->whereBetween('apertura_at', [$inicioHoyUtc, $finHoyUtc])
                ->orderBy('apertura_at', 'desc')
                ->first();
        }

        // 3. Detectar bloqueo: caja vieja ABIERTA (de día anterior)
        $bloqueo = $this->detectarBloqueo($userId, $inicioHoyUtc);

        return [
            'fecha_ar' => $fechaAr,
            'usuario_id' => $userId,
            'caja_actual' => $cajaActual ? $this->formatearCajaActual($cajaActual) : null,
            'ultima_caja_hoy' => $ultimaCajaHoy ? $this->formatearUltimaCaja($ultimaCajaHoy) : null,
            'bloqueo' => $bloqueo,
        ];
    }

    /**
     * Detectar si existe bloqueo por caja vieja abierta
     */
    private function detectarBloqueo(int $userId, Carbon $inicioHoyUtc): array
    {
        // Buscar caja ABIERTA de día anterior (apertura_at < inicio de hoy)
        $cajaVieja = CajaOperativa::where('usuario_operativo_id', $userId)
            ->where('estado', 'ABIERTA')
            ->where('apertura_at', '<', $inicioHoyUtc)
            ->orderBy('apertura_at', 'desc')
            ->first();

        if ($cajaVieja) {
            return [
                'activo' => true,
                'tipo' => 'CAJA_VIEJA_ABIERTA',
                'caja_id' => $cajaVieja->id,
                'mensaje' => 'Tenés una caja abierta de un día anterior. Debés cerrarla antes de operar.',
            ];
        }

        return [
            'activo' => false,
            'tipo' => null,
            'caja_id' => null,
            'mensaje' => null,
        ];
    }

    /**
     * Formatear caja actual (abierta)
     */
    private function formatearCajaActual(CajaOperativa $caja): array
    {
        return [
            'id' => $caja->id,
            'apertura_at' => $caja->apertura_at->toIso8601String(),
            'estado' => $caja->estado,
        ];
    }

    /**
     * Formatear última caja del día
     */
    private function formatearUltimaCaja(CajaOperativa $caja): array
    {
        return [
            'id' => $caja->id,
            'apertura_at' => $caja->apertura_at->toIso8601String(),
            'cierre_at' => $caja->cierre_at?->toIso8601String(),
            'estado' => $caja->estado,
        ];
    }

    /**
     * Convertir fecha Argentina a rango UTC para whereBetween
     */
    private function rangoDelDiaEnUtc(Carbon $fechaAr): array
    {
        $inicioUtc = $fechaAr->copy()->startOfDay()->setTimezone('UTC');
        $finUtc = $fechaAr->copy()->endOfDay()->setTimezone('UTC');

        return [$inicioUtc, $finUtc];
    }
}
