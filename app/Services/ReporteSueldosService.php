<?php

namespace App\Services;

use App\Models\{Clase, Deporte, Profesor};
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/** Análisis sin escrituras. Cuotas devengadas y cobros son bases distintas. */
class ReporteSueldosService
{
    public function __construct(private HistorialAnaliticoReportesService $historial) {}

    public function obtener(string $mes, ?int $deporteId = null): array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $mes) || $mes > today()->format('Y-m')) {
            throw new InvalidArgumentException('Elegí un mes actual o anterior.');
        }
        $inicio = CarbonImmutable::createFromFormat('!Y-m', $mes);
        $corte = $mes === today()->format('Y-m') ? CarbonImmutable::now() : $inicio->endOfMonth();
        $cuotas = $this->historial->obtener('CUOTA', $corte);
        $tarifas = $this->historial->obtener('TARIFA', $corte);
        $pagos = $this->historial->obtener('PAGO', $corte);
        $liquidaciones = $this->historial->obtener('LIQUIDACION', $corte);
        $disponible = $cuotas['disponible'] && $tarifas['disponible'] && $pagos['disponible'] && $liquidaciones['disponible'];
        $clases = Clase::whereBetween('fecha', [$inicio->toDateString(), $corte->toDateString()])->where('cancelada', false)
            ->with(['grupo.deporte','profesores','asistencias'])->get()
            ->filter(fn ($c) => CarbonImmutable::parse($c->fecha->toDateString().' '.$c->hora_fin->format('H:i:s'))->lte($corte));
        $tasas = collect($tarifas['filas'] ?? [])->keyBy('origen_id');
        $liqMes = collect($liquidaciones['filas'] ?? [])->where('periodo',$mes)
            ->filter(fn ($l) => $l['datos']['estado'] !== 'CANCELADA')->groupBy(fn ($l) => $l['persona_id'].':'.$l['deporte_id']);
        $nombresDeportes = Deporte::pluck('nombre','id');
        $profesores = Profesor::whereIn('id',$clases->flatMap(fn ($c) => $c->profesores->pluck('id'))
            ->merge(collect($liquidaciones['filas'] ?? [])->where('periodo',$mes)->pluck('persona_id'))->unique())
            ->get()->keyBy('id');
        $filas = [];
        $pesos = [];
        $presentesUnicos = [];
        $combinaciones = $clases->flatMap(fn ($c) => $c->profesores->map(fn ($p) => $p->id.':'.$c->grupo->deporte_id))
            ->merge($liqMes->keys())->unique();
        foreach ($combinaciones as $claveProfesor) {
            [$pid,$sport] = array_map('intval',explode(':',$claveProfesor));
            $p = $profesores->get($pid);
            if (!$p) continue;
            $filas[$claveProfesor] = ['id'=>$p->id,'nombre'=>$p->apellido.', '.$p->nombre,'deporte_id'=>$sport,
                'deporte'=>$nombresDeportes[$sport] ?? 'Sin deporte','asistencias'=>0,'alumnos_con_pago'=>null,
                'asistencias_sin_pago'=>null,'ingreso'=>$disponible ? 0 : null,'costo'=>null,
                'costo_por_asistencia'=>null,'porcentaje_costo'=>null,'faltan_cuotas'=>false,'base'=>'Sin historial'];
        }
        foreach ($clases as $clase) {
            foreach ($clase->asistencias->where('presente',true) as $a) {
                $presentesUnicos[$a->id] = (int)$clase->grupo->deporte_id;
                foreach ($clase->profesores as $p) {
                    $claveProfesor = $p->id.':'.$clase->grupo->deporte_id;
                    $filas[$claveProfesor]['asistencias']++;
                    $clave = $a->alumno_id.':'.$clase->grupo->deporte_id;
                    $pesos[$clave][$claveProfesor] = ($pesos[$clave][$claveProfesor] ?? 0) + 1;
                }
            }
        }
        $netos = [];
        foreach (collect($cuotas['filas'] ?? [])->where('periodo',$mes) as $f) {
            $clave = $f['persona_id'].':'.$f['deporte_id'];
            $netos[$clave] = ($netos[$clave] ?? 0) + $f['datos']['neto_centavos'];
        }
        $cobrados = [];
        $pagosBase = [];
        foreach (collect($pagos['filas'] ?? [])->where('periodo',$mes) as $f) {
            if (!in_array($f['datos']['estado'],['COMPLETADO','pagado'],true) || !$f['datos']['fecha_pago']
                || $f['datos']['fecha_pago'] > $corte->toDateString()) continue;
            $clave = $f['persona_id'].':'.$f['deporte_id'];
            $cobrados[$clave] = ($cobrados[$clave] ?? 0) + $f['datos']['cuota_centavos'];
            $pagosBase[] = ['clave'=>$clave,'centavos'=>$f['datos']['cuota_centavos']];
        }
        $sinAsistencia = 0;
        $sinCuota = 0;
        foreach ($netos as $clave=>$neto) {
            if (!isset($pesos[$clave])) {
                if ($deporteId === null || (int)explode(':',$clave)[1] === $deporteId) $sinAsistencia += $neto;
                continue;
            }
            // Reparto exacto en centavos; los restos se asignan en orden de profesor.
            $reparto = $this->repartir($neto,$pesos[$clave]);
            foreach ($reparto as $pid=>$importe) $filas[$pid]['ingreso'] += $importe;
        }
        foreach ($filas as $claveProfesor=>&$fila) {
            $pid = $fila['id'];
            if ($disponible) {
                $conPago = [];
                $sinPago = 0;
                foreach ($pesos as $clave=>$profes) {
                    if (!isset($profes[$claveProfesor])) continue;
                    if (($cobrados[$clave] ?? 0) > 0) $conPago[explode(':',$clave)[0]] = true;
                    else $sinPago += $profes[$claveProfesor];
                    if (!array_key_exists($clave,$netos)) {
                        if ($deporteId === null || $fila['deporte_id'] === $deporteId) $sinCuota += $profes[$claveProfesor];
                        $fila['faltan_cuotas'] = true;
                    }
                }
                $fila['alumnos_con_pago'] = count($conPago);
                $fila['asistencias_sin_pago'] = $sinPago;
                $liqs = $liqMes->get($claveProfesor,collect());
                $cerradas = $liqs->filter(fn ($l) => $l['datos']['estado'] === 'CERRADA');
                $base = $cerradas->isNotEmpty() ? $cerradas : $liqs;
                if ($base->count() === 1) {
                    $estado = $base->first()['datos'];
                    $fila['costo'] = $estado['final_centavos'] ?? $estado['calculado_centavos'];
                    $fila['base'] = $cerradas->isNotEmpty() ? 'Liquidación cerrada' : 'Liquidación abierta';
                } elseif ($base->count() > 1) {
                    $fila['base'] = 'Revisar liquidaciones';
                } else {
                    $tarifa = $tasas[$pid] ?? null;
                    $propias = $clases->filter(fn ($c) => $c->profesores->contains('id',$pid) && (int)$c->grupo->deporte_id === $fila['deporte_id']);
                    if ($tarifa && (int)$tarifa['deporte_id'] !== $fila['deporte_id']) {
                        $ultima = $propias->sortBy(fn ($c) => $c->fecha->toDateString().' '.$c->hora_fin->format('H:i:s'))->last();
                        $pasado = $ultima ? $this->historial->obtener('TARIFA',CarbonImmutable::parse($ultima->fecha->toDateString().' '.$ultima->hora_fin->format('H:i:s')),$pid) : null;
                        $tarifa = $pasado['filas'][0] ?? null;
                    }
                    $tasa = $tarifa && (int)$tarifa['deporte_id'] === $fila['deporte_id'] ? $tarifa['datos'] : null;
                    // Una liquidación del mismo docente en otro deporte no se duplica como estimado.
                    $liquidadoEnOtro = collect($liquidaciones['filas'] ?? [])->where('periodo',$mes)->where('persona_id',$pid)
                        ->contains(fn ($l) => $l['datos']['estado'] !== 'CANCELADA');
                    if ($liquidadoEnOtro) $tasa = null;
                    if ($tasa && $tasa['tipo'] === 'COMISION' && $tasa['comision_centesimas'] !== null) {
                        $fila['costo'] = 0;
                        foreach ($pagosBase as $pago) {
                            if (isset($pesos[$pago['clave']][$claveProfesor])) $fila['costo'] += (int)round($pago['centavos'] * $tasa['comision_centesimas'] / 10000);
                        }
                        $fila['base'] = 'Comisión sobre cuotas cobradas';
                    } elseif ($tasa && $tasa['tipo'] === 'HORA' && $tasa['hora_centavos'] !== null) {
                        $validas = $propias->filter(fn ($c) => $c->validada_para_liquidacion || $c->asistencias->contains('presente',true));
                        $completas = $validas->every(fn ($c) => CarbonImmutable::parse($c->fecha->toDateString().' '.$c->hora_fin->format('H:i:s'))
                            ->gte(CarbonImmutable::parse($tarifas['desde'])));
                        if ($completas) {
                            $fila['costo'] = 0;
                            foreach ($validas as $c) {
                                $minutos = (int)$c->hora_inicio->diffInMinutes($c->hora_fin,false);
                                if ($minutos <= 0) { $fila['costo'] = null; break; }
                                $fila['costo'] += (int)round($tasa['hora_centavos'] * $minutos / 60);
                            }
                            $fila['base'] = $fila['costo'] === null ? 'Horario sin datos' : 'Estimado por clases del mes';
                        }
                    }
                }
                if ($fila['faltan_cuotas']) $fila['ingreso'] = null;
                $fila['costo_por_asistencia'] = $fila['costo'] !== null && $fila['asistencias'] > 0 ? (int)round($fila['costo']/$fila['asistencias']) : null;
                $fila['porcentaje_costo'] = $fila['costo'] !== null && $fila['ingreso'] > 0 ? round($fila['costo']*100/$fila['ingreso'],1) : null;
            }
        }
        unset($fila);
        $filas = collect($filas)->when($deporteId !== null,fn ($f) => $f->where('deporte_id',$deporteId))->sortByDesc('costo')->values();
        $porDeporte = $filas->groupBy('deporte_id')->map(fn ($g) => ['nombre'=>$g->first()['deporte'],
            'costo'=>$disponible && $g->every(fn ($f) => $f['costo'] !== null) ? $g->sum('costo') : null,
            'ingreso'=>$disponible && $g->every(fn ($f) => $f['ingreso'] !== null) ? $g->sum('ingreso') : null,
            'asistencias'=>count(array_filter($presentesUnicos,fn ($sport) => $sport === $g->first()['deporte_id']))])->values()->all();
        $costo = $disponible && $filas->every(fn ($f) => $f['costo'] !== null) ? (int)$filas->sum('costo') : null;
        $ingreso = $disponible && $filas->every(fn ($f) => $f['ingreso'] !== null) ? (int)$filas->sum('ingreso') : null;
        $asistencias = count(array_filter($presentesUnicos,fn ($sport) => $deporteId === null || $sport === $deporteId));
        return ['mes'=>$mes,'deporte_id'=>$deporteId,'fecha_corte'=>$corte->toDateString(),'historial_desde'=>$cuotas['desde'],
            'disponible'=>$disponible,'costo'=>$costo,'ingreso'=>$ingreso,'asistencias'=>$asistencias,
            'costo_por_asistencia'=>$costo !== null && $asistencias > 0 ? (int)round($costo/$asistencias) : null,
            'porcentaje_costo'=>$costo !== null && $ingreso > 0 ? round($costo*100/$ingreso,1) : null,
            'sin_asistencia'=>$disponible ? $sinAsistencia : null,'asistencias_sin_cuota'=>$disponible ? $sinCuota : null,
            'profesores'=>$filas->all(),'deportes'=>$porDeporte];
    }

    private function repartir(int $centavos, array $pesos): array
    {
        ksort($pesos);
        $total = array_sum($pesos);
        $resultado = [];
        $restos = [];
        foreach ($pesos as $id=>$peso) {
            $resultado[$id] = intdiv($centavos*$peso,$total);
            $restos[$id] = ($centavos*$peso)%$total;
        }
        arsort($restos);
        $faltan = $centavos-array_sum($resultado);
        foreach (array_keys($restos) as $id) { if ($faltan-- <= 0) break; $resultado[$id]++; }
        return $resultado;
    }

    public function evolucion(string $mes, ?int $deporteId = null): array
    {
        $this->obtener($mes,$deporteId);
        $fin = CarbonImmutable::createFromFormat('!Y-m',$mes);
        if ($mes === today()->format('Y-m')) $fin = $fin->subMonth();
        $filas = [];
        for ($p = $fin->subMonths(5); $p <= $fin; $p = $p->addMonth()) $filas[] = $this->obtener($p->format('Y-m'),$deporteId);
        return $filas;
    }
}
