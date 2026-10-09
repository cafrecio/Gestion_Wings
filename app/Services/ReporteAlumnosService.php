<?php

namespace App\Services;

use App\Models\{Alumno, AlumnoRevisionCobranza, Clase};
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/** Consulta sin escrituras. La matrícula actual nunca se presenta como histórica. */
class ReporteAlumnosService
{
    public function obtener(string $mes, ?int $deporteId = null): array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $mes) || $mes > today()->format('Y-m')) {
            throw new InvalidArgumentException('Elegí un mes actual o anterior.');
        }
        $inicio = CarbonImmutable::createFromFormat('!Y-m', $mes);
        $fin = min($inicio->endOfMonth()->toDateString(), today()->toDateString());
        $clases = Clase::whereBetween('fecha', [$inicio->toDateString(), $fin])->where('cancelada', false)
            ->when($deporteId !== null, fn ($q) => $q->whereHas('grupo', fn ($g) => $g->where('deporte_id', $deporteId)))
            ->with(['grupo.deporte', 'grupo.nivel', 'asistencias.alumno'])->get();
        $registros = $clases->flatMap(fn ($clase) => $clase->asistencias);
        $presentes = $registros->where('presente', true);
        $ausentes = $registros->where('presente', false);
        $alumnos = Alumno::when($deporteId !== null, fn ($q) => $q->where('deporte_id', $deporteId))
            ->with(['deporte', 'grupo.nivel'])->orderBy('apellido')->orderBy('nombre')->get();
        $activos = $alumnos->where('activo', true);
        $asistieron = $presentes->pluck('alumno_id')->unique();
        $sinPresentes = $activos->whereNotIn('id', $asistieron)->map(fn ($a) => [
            'id' => $a->id, 'nombre' => $a->apellido.', '.$a->nombre,
            'deporte' => $a->deporte?->nombre ?? 'Sin deporte', 'nivel' => $a->grupo?->nivel?->nombre ?? 'Sin nivel',
        ])->values()->all();
        $grupos = $clases->groupBy('grupo_id')->map(function ($grupo) {
            $clase = $grupo->first();
            $filas = $grupo->flatMap(fn ($c) => $c->asistencias);
            return ['grupo_id' => $clase->grupo_id,
                'nombre' => ($clase->grupo?->deporte?->nombre ?? 'Sin deporte').' · '.($clase->grupo?->nivel?->nombre ?? 'Sin nivel'),
                'presentes' => $filas->where('presente', true)->count(), 'ausentes' => $filas->where('presente', false)->count(),
                'sin_registros' => $grupo->filter(fn ($c) => $c->asistencias->isEmpty())->count(),
            ];
        })->sortByDesc('presentes')->values()->all();
        return [
            'mes' => $mes, 'fecha_corte' => $fin, 'deporte_id' => $deporteId,
            'fecha_matricula' => today()->toDateString(), 'activos' => $activos->count(),
            'inactivos' => $alumnos->where('activo', false)->count(),
            'por_deporte' => $activos->groupBy('deporte_id')->map(fn ($g) => [
                'nombre' => $g->first()->deporte?->nombre ?? 'Sin deporte', 'cantidad' => $g->count(),
            ])->values()->all(),
            'por_nivel' => $activos->groupBy(fn ($a) => $a->deporte_id.':'.$a->grupo?->nivel_id)->map(fn ($g) => [
                'nombre' => ($g->first()->deporte?->nombre ?? 'Sin deporte').' · '.($g->first()->grupo?->nivel?->nombre ?? 'Sin nivel'),
                'cantidad' => $g->count(),
            ])->values()->all(),
            'presentes' => $presentes->count(), 'ausentes' => $ausentes->count(),
            'porcentaje_presencia' => $registros->isEmpty() ? null : round($presentes->count() * 100 / $registros->count(), 1),
            'alumnos_asistieron' => $asistieron->count(), 'clases' => $clases->count(),
            'clases_sin_registros' => $clases->filter(fn ($c) => $c->asistencias->isEmpty())->count(),
            'grupos' => $grupos, 'sin_presentes_actuales' => $sinPresentes,
            'revisiones' => AlumnoRevisionCobranza::where('estado_revision', 'PENDIENTE')
                ->when($deporteId !== null, fn ($q) => $q->whereHas('alumno', fn ($a) => $a->where('deporte_id', $deporteId)))->count(),
        ];
    }

    public function evolucion(string $mes, ?int $deporteId = null): array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $mes) || $mes > today()->format('Y-m')) {
            throw new InvalidArgumentException('Elegí un mes actual o anterior.');
        }
        $limite = $mes === today()->format('Y-m') ? $mes
            : CarbonImmutable::createFromFormat('!Y-m', $mes)->addMonth()->format('Y-m');
        // Solo meses cerrados con clases: un hueco no demuestra cero asistencia.
        $meses = Clase::where('cancelada', false)->whereDate('fecha', '<', $limite.'-01')
            ->when($deporteId !== null, fn ($q) => $q->whereHas('grupo', fn ($g) => $g->where('deporte_id', $deporteId)))
            ->selectRaw("DISTINCT DATE_FORMAT(fecha, '%Y-%m') AS mes")->orderByDesc('mes')->limit(6)->pluck('mes')->reverse();
        return $meses->map(function ($p) use ($deporteId) {
            $r = $this->obtener($p, $deporteId);
            return ['mes' => $p, 'presentes' => $r['clases_sin_registros'] ? null : $r['presentes'],
                'ausentes' => $r['clases_sin_registros'] ? null : $r['ausentes'],
                'registrados' => $r['presentes'], 'ausentes_registrados' => $r['ausentes'], 'sin_registros' => $r['clases_sin_registros']];
        })->values()->all();
    }
}
