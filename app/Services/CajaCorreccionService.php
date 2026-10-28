<?php

namespace App\Services;

use App\Models\{CajaOperativa, MovimientoOperativo, MovimientoOperativoCorreccion, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CajaCorreccionService
{
    /** Caja primero, movimiento después: mismo orden de bloqueo que CajaService. */
    public function corregir(int $cajaId, int $movimientoId, User $actor, ?array $datos): void
    {
        DB::transaction(function () use ($cajaId, $movimientoId, $actor, $datos) {
            $caja = CajaOperativa::whereKey($cajaId)->lockForUpdate()->firstOrFail();
            $usuario = User::findOrFail($actor->id);
            abort_unless($usuario->activo && ($usuario->isAdmin()
                || ($usuario->isOperativo() && $caja->usuario_operativo_id === $usuario->id)), 403);
            if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'], true)) {
                throw ValidationException::withMessages(['movimiento' => 'La caja no está en estado editable.']);
            }
            $movimiento = MovimientoOperativo::where('caja_operativa_id', $cajaId)
                ->lockForUpdate()->findOrFail($movimientoId);
            if (!$movimiento->esManualEditable()) {
                throw ValidationException::withMessages(['movimiento' => 'Los cobros y movimientos automáticos no se editan ni eliminan. Los cobros se cancelan con motivo.']);
            }
            $antes = $this->foto($movimiento);
            if ($datos === null) {
                $movimiento->delete();
                $despues = null;
            } else {
                $movimiento = app(CajaService::class)->actualizarMovimientoEnCaja($cajaId, $movimientoId, $datos);
                $despues = $this->foto($movimiento->refresh());
            }
            MovimientoOperativoCorreccion::create([
                'caja_operativa_id' => $cajaId, 'movimiento_id' => $movimientoId,
                'usuario_id' => $usuario->id, 'autor' => $usuario->name,
                'accion' => $datos === null ? 'ELIMINAR' : 'EDITAR', 'antes' => $antes, 'despues' => $despues,
            ]);
        });
    }

    private function foto(MovimientoOperativo $movimiento): array
    {
        $movimiento->load('subrubro.rubro', 'tipoCaja');
        return $movimiento->only($movimiento->getFillable()) + [
            'subrubro' => $movimiento->subrubro->nombre,
            'rubro' => $movimiento->subrubro->rubro->nombre,
            'tipo' => $movimiento->subrubro->rubro->tipo,
            'permitido_para' => $movimiento->subrubro->permitido_para,
            'medio' => $movimiento->tipoCaja->nombre,
        ];
    }
}
