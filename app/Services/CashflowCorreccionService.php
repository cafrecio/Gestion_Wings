<?php

namespace App\Services;

use App\Models\{CashflowCorreccion, CashflowMovimiento, Subrubro, User};
use App\Rules\ImportePesos;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\{DB, Schema, Validator};
use Illuminate\Validation\ValidationException;

/** T27: original, versión e historial se escriben bajo el mismo bloqueo y transacción. */
class CashflowCorreccionService
{
    public static function disponible(): bool
    {
        return Schema::hasTable('cashflow_correcciones') && Schema::hasColumn('cashflow_movimientos', 'version_correccion');
    }

    public function editar(int $id, int $adminId, array $datos): CashflowMovimiento
    {
        return $this->corregir($id, $adminId, $datos, 'EDITAR');
    }

    public function eliminar(int $id, int $adminId, array $datos): CashflowMovimiento
    {
        return $this->corregir($id, $adminId, $datos, 'ELIMINAR');
    }

    private function corregir(int $id, int $adminId, array $datos, string $accion): CashflowMovimiento
    {
        $admin = User::findOrFail($adminId);
        if (!$admin->isAdmin() || !$admin->isActivo()) throw new AuthorizationException('Solo el administrador activo puede corregir directos.');
        if (!self::disponible()) throw new \RuntimeException('Primero debe prepararse el registro de correcciones.');
        $reglas = ['clave' => 'required|uuid', 'version' => 'required|integer|min:0|max:4294967294',
            'motivo' => 'nullable|string|max:500'];
        if ($accion === 'EDITAR') $reglas += [
            'monto' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', new ImportePesos],
            'fecha' => 'required|date_format:Y-m-d|before_or_equal:today',
            'tipo_caja_id' => 'required|integer|exists:tipos_caja,id',
            'subrubro_id' => 'required|integer|exists:subrubros,id',
            'observaciones' => 'required|string|max:500',
        ];
        $datos = Validator::make($datos, $reglas)->validate();
        $datos['version'] = (int) $datos['version'];
        $motivo = trim($datos['motivo'] ?? '');
        $datos['motivo'] = $motivo === '' ? null : $motivo;
        if ($accion === 'EDITAR') {
            [$entero, $decimal] = array_pad(explode('.', (string) $datos['monto'], 2), 2, '');
            $datos['monto'] = (ltrim($entero, '0') ?: '0').'.'.str_pad($decimal, 2, '0');
            $datos['tipo_caja_id'] = (int) $datos['tipo_caja_id'];
            $datos['subrubro_id'] = (int) $datos['subrubro_id'];
        }
        $huella = hash('sha256', json_encode([$accion, $datos], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($id, $adminId, $datos, $accion, $huella) {
            $mov = CashflowMovimiento::with(['subrubro.rubro', 'tipoCaja'])->lockForUpdate()->findOrFail($id);
            if (!$mov->esDirecto()) throw new AuthorizationException('Usá el recorrido propio del origen de este movimiento.');
            $repetida = CashflowCorreccion::where('clave', $datos['clave'])->first();
            if ($repetida) {
                if ($repetida->movimiento_id !== $id || $repetida->admin_id !== $adminId || $repetida->huella !== $huella) {
                    throw ValidationException::withMessages(['clave' => 'Esta operación ya se usó con otros datos. Recargá la pantalla.']);
                }
                return $mov;
            }
            if (!$mov->puedeCorregirse()) throw new AuthorizationException('Este movimiento ya fue anulado o pertenece a un subrubro protegido.');
            if ($mov->version_correccion !== $datos['version']) {
                throw ValidationException::withMessages(['version' => 'El movimiento cambió mientras editabas. Recargá la pantalla antes de guardar.']);
            }
            $antes = $this->foto($mov);
            if ($accion === 'EDITAR') {
                $sub = Subrubro::with('rubro')->findOrFail($datos['subrubro_id']);
                if ($sub->es_reservado_sistema) throw ValidationException::withMessages(['subrubro_id' => 'Usá el recorrido propio de este subrubro.']);
                // El formulario muestra el tipo actual del rubro seleccionado. Si cambió,
                // su signo y la clasificación del registro corregido deben coincidir con él.
                if ($mov->reporte_tipo !== null && $mov->reporte_tipo !== $sub->rubro?->tipo) {
                    $mov->reporte_tipo = $sub->rubro?->tipo;
                    $mov->reporte_clasificacion = $sub->clasificacion_resultado;
                }
                $mov->fill(array_intersect_key($datos, array_flip(['fecha', 'tipo_caja_id', 'subrubro_id', 'observaciones'])));
                $mov->monto = ($sub->rubro?->tipo === 'EGRESO' ? '-' : '').$datos['monto'];
            } else {
                // Misma fecha y clasificación congelada: revierte el mes original, no el actual.
                CashflowMovimiento::create([
                    'fecha' => $mov->fecha->toDateString(), 'subrubro_id' => $mov->subrubro_id,
                    'tipo_caja_id' => $mov->tipo_caja_id,
                    'monto' => str_starts_with($mov->monto, '-') ? substr($mov->monto, 1) : '-'.$mov->monto,
                    'observaciones' => 'Anulación de directo #'.$mov->id,
                    'usuario_admin_id' => $adminId, 'referencia_tipo' => CashflowMovimiento::REF_CORRECCION,
                    'referencia_id' => $mov->id, 'contra_origen_id' => $mov->id,
                    'reporte_tipo' => $mov->reporte_tipo, 'reporte_clasificacion' => $mov->reporte_clasificacion,
                    'reporte_deporte_id' => $mov->reporte_deporte_id,
                ]);
                $mov->anulado_at = now();
            }
            $mov->version_correccion++;
            $mov->save();
            $mov->refresh()->load(['subrubro.rubro', 'tipoCaja']);
            CashflowCorreccion::create(['movimiento_id' => $id, 'admin_id' => $adminId, 'accion' => $accion,
                'clave' => $datos['clave'], 'huella' => $huella, 'version' => $mov->version_correccion,
                'antes' => $antes, 'despues' => $this->foto($mov), 'motivo' => $datos['motivo']]);
            return $mov;
        });
    }

    private function foto(CashflowMovimiento $mov): array
    {
        return ['fecha' => $mov->fecha->toDateString(), 'monto' => $mov->monto,
            'subrubro_id' => $mov->subrubro_id, 'subrubro' => $mov->subrubro?->nombre,
            'tipo_caja_id' => $mov->tipo_caja_id, 'medio' => $mov->tipoCaja?->nombre,
            'observaciones' => $mov->observaciones, 'usuario_admin_id' => $mov->usuario_admin_id,
            'reporte_tipo' => $mov->reporte_tipo, 'reporte_clasificacion' => $mov->reporte_clasificacion,
            'reporte_deporte_id' => $mov->reporte_deporte_id, 'anulado' => $mov->anulado_at !== null];
    }
}
