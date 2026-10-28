<?php

namespace App\Services;

use App\Models\{Deporte, DeporteLiquidacionVigencia, Liquidacion, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeporteLiquidacionService
{
    public function actualizar(int $id, string $nombre, string $tipo, User $actor): bool
    {
        return DB::transaction(function () use ($id, $nombre, $tipo, $actor) {
            $usuario = User::findOrFail($actor->id);
            abort_unless($usuario->activo && $usuario->isAdmin(), 403);
            abort_unless(in_array($tipo, ['HORA', 'COMISION'], true), 422);
            $deporte = Deporte::whereKey($id)->lockForUpdate()->firstOrFail();
            $desde = today()->startOfMonth()->addMonth();
            $cambia = $tipo !== $deporte->tipoLiquidacionParaPeriodo($desde->month, $desde->year);
            if ($cambia) {
                $abiertas = Liquidacion::where('estado', Liquidacion::ESTADO_ABIERTA)
                    ->whereHas('profesor', fn ($q) => $q->where('deporte_id', $id))
                    ->with('profesor')->orderBy('id')->lockForUpdate()->get();
                if ($abiertas->isNotEmpty()) {
                    $lista = $abiertas->map(fn ($l) => '#'.$l->id.' '.$l->profesor->nombre_completo.' '.sprintf('%02d/%d', $l->mes, $l->anio))->join('; ');
                    throw ValidationException::withMessages(['tipo_liquidacion' => 'No se puede cambiar la forma de liquidar: hay liquidaciones abiertas: '.$lista.'.']);
                }
                DeporteLiquidacionVigencia::create(['deporte_id' => $id, 'tipo' => $tipo,
                    'vigente_desde' => $desde->toDateString(), 'usuario_id' => $usuario->id]);
            }
            // La columna original conserva la base anterior a la primera vigencia.
            $deporte->update(['nombre' => $nombre]);
            return $cambia;
        });
    }
}
