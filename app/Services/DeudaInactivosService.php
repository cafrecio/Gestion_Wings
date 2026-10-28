<?php

namespace App\Services;

use App\Models\{Alumno, CargoAlumno, DeudaCuota, DeudaPerdida, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Consulta actual separada: nunca alimenta Inicio ni el por cobrar. */
class DeudaInactivosService
{
    public function obtener(?int $deporteId = null, ?int $grupoId = null): array
    {
        $alumnos = Alumno::where('activo', false)->with('deudaCuotas')->orderBy('apellido')->orderBy('nombre')->orderBy('id')->get();
        $activos = Alumno::where('activo', true)->pluck('dni')->map(fn ($dni) => InscripcionService::dni($dni ?? ''))->filter()->flip();
        $dnis = $alumnos->pluck('dni')->map(fn ($dni) => InscripcionService::dni($dni ?? ''))->filter()->unique();
        $cargos = $alumnos->isEmpty() ? collect() : CargoAlumno::where('estado', 'VIGENTE')
            ->where(fn ($q) => $q->whereIn('alumno_id', $alumnos->pluck('id'))->orWhereIn('dni', $dnis))
            ->with(['pagos', 'deudaCuota'])->get();
        return $this->construir($alumnos, $activos, $cargos, $deporteId, $grupoId);
    }

    private function construir($alumnos, $activos, $cargos, ?int $deporteId = null, ?int $grupoId = null): array
    {
        $filas = $alumnos->mapWithKeys(fn ($a) => [$a->id => [
            'id' => $a->id, 'nombre' => $a->apellido.', '.$a->nombre,
            'deporte_id' => $a->deporte_id, 'grupo_id' => $a->grupo_id,
            'inactivo_desde' => $a->inactivo_desde?->format('Y-m-d H:i:s'), 'partidas' => [],
        ]])->all();
        foreach ($alumnos as $alumno) {
            foreach ($alumno->deudaCuotas as $cuota) {
                if ($cuota->periodo > now()->format('Y-m') || in_array($cuota->estado, ['CONDONADA', 'PAGADA'])) continue;
                $centavos = (int) round($cuota->saldo_pendiente * 100);
                if ($centavos > 0) $filas[$alumno->id]['partidas'][] = ['tipo' => 'CUOTA', 'id' => $cuota->id, 'centavos' => $centavos];
            }
        }
        foreach ($cargos as $cargo) {
            $destino = $alumnos->firstWhere('id', $cargo->alumno_id);
            if ($cargo->tipo === 'INSCRIPCION') {
                $dni = InscripcionService::dni($cargo->dni ?? '');
                // La inscripción ya imputada a un activo no aparece también acá.
                if ($activos->has($dni)) continue;
                $destino ??= $alumnos->first(fn ($a) => $dni !== '' && InscripcionService::dni($a->dni ?? '') === $dni);
            } elseif ($cargo->tipo !== 'PUNITORIO' || !$cargo->deudaCuota || $cargo->deudaCuota->periodo > now()->format('Y-m')) {
                continue;
            }
            if (!$destino) continue;
            $centavos = max(0, (int) round(((float) $cargo->monto_original - (float) $cargo->monto_condonado
                - (float) $cargo->monto_perdido - (float) $cargo->pagos->sum('pivot.monto_aplicado')) * 100));
            if ($centavos > 0) $filas[$destino->id]['partidas'][] = ['tipo' => $cargo->tipo, 'id' => $cargo->id, 'centavos' => $centavos];
        }
        $filas = collect($filas)->map(function ($fila) {
            $fila['centavos'] = array_sum(array_column($fila['partidas'], 'centavos'));
            return $fila;
        })->filter(fn ($f) => $f['centavos'] > 0 && (!$deporteId || $f['deporte_id'] == $deporteId) && (!$grupoId || $f['grupo_id'] == $grupoId))->values();
        return ['total_centavos' => $filas->sum('centavos'), 'filas' => $filas->all(), 'fecha' => today()->toDateString()];
    }

    public function perder(int $alumnoId, int $esperadoCentavos, User $usuario): DeudaPerdida
    {
        abort_unless($usuario->isAdmin() && $usuario->isActivo(), 403);
        $referencia = Alumno::findOrFail($alumnoId);
        return DB::transaction(function () use ($alumnoId, $esperadoCentavos, $usuario, $referencia) {
            app(InscripcionService::class)->bloquear($referencia->dni, false);
            // Mismo orden que cobrar: persona, alumnos, cuotas y cargos. Lecturas actuales
            // bajo bloqueo, incluso si hubo que esperar un cobro o una reactivación.
            $dni = InscripcionService::dni($referencia->dni ?? '');
            $familia = Alumno::where(fn ($q) => $q->whereKey($alumnoId)->when($dni !== '', fn ($q) =>
                $q->orWhereRaw("REPLACE(REPLACE(REPLACE(dni, '.', ''), '-', ''), ' ', '') = ?", [$dni])))
                ->orderBy('id')->lockForUpdate()->get();
            $alumno = $familia->firstWhere('id', $alumnoId);
            abort_unless($alumno, 404);
            if ($alumno->activo) $this->rechazar('El alumno volvió a activo. Actualizá la lista.');
            $cuotas = DeudaCuota::whereIn('alumno_id', $familia->pluck('id'))->orderBy('periodo')->orderBy('id')->lockForUpdate()->get();
            foreach ($familia as $registro) $registro->setRelation('deudaCuotas', $cuotas->where('alumno_id', $registro->id));
            $cargos = CargoAlumno::where(fn ($q) => $q->whereIn('alumno_id', $familia->pluck('id'))->when($dni !== '', fn ($q) => $q->orWhere('dni', $dni)))
                ->where('estado', 'VIGENTE')->orderBy('id')->lockForUpdate()->get();
            // También las imputaciones se leen con bloqueo, como CargoAlumno::monto_cobrado.
            foreach ($cargos as $cargo) {
                $cargo->setRelation('pagos', $cargo->pagos()->lockForUpdate()->get());
                $cargo->setRelation('deudaCuota', $cuotas->firstWhere('id', $cargo->deuda_cuota_id));
            }
            $activos = $familia->where('activo', true)->pluck('dni')->map(fn ($d) => InscripcionService::dni($d ?? ''))->filter()->flip();
            $calculado = $this->construir($familia->where('activo', false), $activos, $cargos);
            $fila = collect($calculado['filas'])->firstWhere('id', $alumnoId);
            if (!$fila || $fila['centavos'] !== $esperadoCentavos) $this->rechazar('La deuda cambió. Actualizá la lista antes de darla por perdida.');
            foreach ($fila['partidas'] as $parte) {
                $modelo = $parte['tipo'] === 'CUOTA' ? DeudaCuota::findOrFail($parte['id']) : CargoAlumno::findOrFail($parte['id']);
                $modelo->forceFill(['monto_perdido' => round((float) $modelo->monto_perdido + $parte['centavos'] / 100, 2)])->save();
            }
            return DeudaPerdida::create(['alumno_id' => $alumnoId, 'usuario_id' => $usuario->id,
                'importe' => $fila['centavos'] / 100, 'detalle' => $fila['partidas'], 'created_at' => now()]);
        });
    }

    private function rechazar(string $mensaje): never
    {
        throw ValidationException::withMessages(['deuda' => $mensaje]);
    }
}
