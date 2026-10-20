<?php

namespace App\Services;

use App\Models\{CashflowMovimiento, Subrubro, TipoCaja, User};
use App\Rules\ImportePesos;
use App\Support\ClasificacionSubrubros;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\ValidationException;

/** Una transferencia es indivisible: salida y entrada se confirman juntas. */
class TransferenciaCashflowService
{
    public function registrar(int $adminId, array $datos): int
    {
        $admin = User::findOrFail($adminId);
        if (!$admin->isAdmin() || !$admin->isActivo()) throw new AuthorizationException();
        $datos = $this->validarDatos($datos);

        return DB::transaction(function () use ($adminId, $datos) {
            return $this->guardar($adminId, $datos);
        }, 3);
    }

    public function validarDatos(array $datos): array
    {
        $datos = Validator::make($datos, [
            'clave' => 'required|uuid',
            'salida_id' => 'required|integer|exists:tipos_caja,id',
            'destino_id' => 'required|integer|different:salida_id|exists:tipos_caja,id',
            'monto' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', new ImportePesos],
            'fecha' => 'required|date_format:Y-m-d|before_or_equal:today',
            'observaciones' => 'nullable|string|max:500',
        ], ['destino_id.different' => 'Elegí una cuenta de destino distinta de la salida.'])->validate();
        [$entero, $decimal] = array_pad(explode('.', (string) $datos['monto'], 2), 2, '');
        $datos['monto'] = (ltrim($entero, '0') ?: '0').'.'.str_pad($decimal, 2, '0');
        $datos['salida_id'] = (int) $datos['salida_id'];
        $datos['destino_id'] = (int) $datos['destino_id'];
        $datos['observaciones'] = trim($datos['observaciones'] ?? '') ?: null;
        return $datos;
    }

    private function guardar(int $adminId, array $datos): int
    {
            // Orden estable y candados: doble envío y transferencias cruzadas se serializan.
            $cuentas = TipoCaja::whereIn('id', [$datos['salida_id'], $datos['destino_id']])
                ->orderBy('id')->lockForUpdate()->get();
            $previa = DB::table('cashflow_transferencias')->where('clave', $datos['clave'])->first();
            if ($previa) {
                foreach (['salida_id', 'destino_id', 'monto', 'fecha', 'observaciones'] as $campo) {
                    if ((string) $previa->$campo !== (string) $datos[$campo]) {
                        throw ValidationException::withMessages(['clave' => 'Esta transferencia ya se registró con otros datos. Recargá la pantalla.']);
                    }
                }
                if ((int) $previa->admin_id !== $adminId) throw new AuthorizationException();
                return (int) $previa->id;
            }
            if ($cuentas->count() !== 2 || $cuentas->contains(fn ($c) => !$c->activo)) {
                throw ValidationException::withMessages(['destino_id' => 'Ambas cuentas deben estar activas.']);
            }
            $conceptos = [];
            foreach (ClasificacionSubrubros::TRASPASOS as $concepto) {
                $sub = Subrubro::where('nombre', $concepto['subrubro'])
                    ->where('es_reservado_sistema', true)->where('activo', true)
                    ->where('clasificacion_resultado', ClasificacionSubrubros::TRASPASO)
                    ->where('permitido_para', 'ADMIN')
                    ->whereHas('rubro', fn ($q) => $q->where('nombre', $concepto['rubro'])
                        ->where('tipo', $concepto['tipo'])->where('es_reservado_sistema', true))->first();
                if (!$sub) throw ValidationException::withMessages(['destino_id' => 'El catálogo de transferencias necesita revisión. No se guardó la operación.']);
                $conceptos[$concepto['tipo']] = $sub;
            }
            $id = DB::table('cashflow_transferencias')->insertGetId($datos + [
                'admin_id' => $adminId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (['EGRESO' => 'salida_id', 'INGRESO' => 'destino_id'] as $tipo => $campo) {
                CashflowMovimiento::create([
                    'fecha' => $datos['fecha'], 'subrubro_id' => $conceptos[$tipo]->id,
                    'tipo_caja_id' => $datos[$campo],
                    'monto' => ($tipo === 'EGRESO' ? '-' : '').$datos['monto'],
                    'observaciones' => $datos['observaciones'], 'usuario_admin_id' => $adminId,
                    'referencia_tipo' => CashflowMovimiento::REF_TRANSFERENCIA, 'referencia_id' => $id,
                ]);
            }
            return $id;
    }
}
