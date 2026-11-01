<?php

namespace App\Services;

use App\Models\{CashflowMovimiento, TipoCaja, User};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\ValidationException;

/** T97: revierte una transferencia entera; nunca habilita corregir una pata suelta. */
class AnulacionTransferenciaCashflowService
{
    public function desdeMovimiento(int $movimientoId): object
    {
        $movimiento = CashflowMovimiento::findOrFail($movimientoId);
        if ($movimiento->referencia_tipo !== CashflowMovimiento::REF_TRANSFERENCIA
            || $movimiento->contra_origen_id !== null) {
            throw new AuthorizationException('Solo se anula la transferencia desde uno de sus movimientos originales.');
        }
        $transferencia = DB::table('cashflow_transferencias as t')
            ->join('tipos_caja as salida', 'salida.id', '=', 't.salida_id')
            ->join('tipos_caja as destino', 'destino.id', '=', 't.destino_id')
            ->where('t.id', $movimiento->referencia_id)
            ->select('t.*', 'salida.nombre as salida_nombre', 'destino.nombre as destino_nombre')->first();
        if (!$transferencia) $this->inconsistente();
        return $transferencia;
    }

    public function anular(int $movimientoId, int $adminId, array $datos): int
    {
        $admin = User::findOrFail($adminId);
        if (!$admin->isAdmin() || !$admin->isActivo()) throw new AuthorizationException();
        if (isset($datos['motivo']) && is_string($datos['motivo'])) $datos['motivo'] = preg_replace('/^\s+|\s+$/u', '', $datos['motivo']);
        $datos = Validator::make($datos, ['motivo' => 'required|string|max:500'],
            ['motivo.required' => 'Indicá el motivo de la anulación.'])->validate();
        $origen = $this->desdeMovimiento($movimientoId);

        return DB::transaction(function () use ($origen, $adminId, $datos) {
            // Mismo orden que registrar(): ambas cuentas antes de cabecera y movimientos.
            // También permite revertir una cuenta desactivada, conservando su historial.
            $cuentas = TipoCaja::whereIn('id', [$origen->salida_id, $origen->destino_id])
                ->orderBy('id')->lockForUpdate()->get();
            $transferencia = DB::table('cashflow_transferencias')->where('id', $origen->id)->lockForUpdate()->first();
            if (!$transferencia || $cuentas->count() !== 2) $this->inconsistente();
            if ($transferencia->anulada_at !== null) {
                throw ValidationException::withMessages(['motivo' => 'La transferencia ya está anulada.']);
            }
            $patas = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)
                ->where('referencia_id', $transferencia->id)->orderBy('id')->lockForUpdate()->get();
            if ($patas->count() !== 2) $this->inconsistente();
            foreach (['salida_id' => '-', 'destino_id' => ''] as $cuenta => $signo) {
                $candidatas = $patas->where('tipo_caja_id', $transferencia->$cuenta);
                if ($candidatas->count() !== 1) $this->inconsistente();
                $pata = $candidatas->first();
                if ($pata->contra_origen_id !== null || $pata->movimiento_operativo_id !== null
                    || $pata->anulado_at !== null || $pata->monto !== $signo.$transferencia->monto
                    || $pata->fecha->toDateString() !== $transferencia->fecha) $this->inconsistente();
            }
            if (CashflowMovimiento::whereIn('contra_origen_id', $patas->modelKeys())->exists()) $this->inconsistente();

            foreach ($patas as $pata) {
                CashflowMovimiento::create([
                    'fecha' => $pata->fecha->toDateString(), 'subrubro_id' => $pata->subrubro_id,
                    'tipo_caja_id' => $pata->tipo_caja_id,
                    'monto' => str_starts_with($pata->monto, '-') ? substr($pata->monto, 1) : '-'.$pata->monto,
                    'observaciones' => 'Anulación de transferencia #'.$transferencia->id,
                    'usuario_admin_id' => $adminId,
                    'referencia_tipo' => CashflowMovimiento::REF_ANULACION_TRANSFERENCIA,
                    'referencia_id' => $transferencia->id, 'contra_origen_id' => $pata->id,
                    'reporte_tipo' => $pata->reporte_tipo, 'reporte_clasificacion' => $pata->reporte_clasificacion,
                    'reporte_deporte_id' => $pata->reporte_deporte_id,
                ]);
                $pata->anulado_at = now();
                $pata->version_correccion++;
                $pata->save();
            }
            DB::table('cashflow_transferencias')->where('id', $transferencia->id)->update([
                'anulada_at' => now(), 'anulo_admin_id' => $adminId,
                'motivo_anulacion' => $datos['motivo'], 'updated_at' => now(),
            ]);
            return (int) $transferencia->id;
        }, 3);
    }

    private function inconsistente(): never
    {
        throw ValidationException::withMessages(['motivo' => 'Los movimientos de la transferencia necesitan revisión. No se anuló la operación.']);
    }
}
