<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Configuracion;
use App\Models\CargoAlumno;
use App\Models\DeudaCuota;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CobranzaEstadoService
{
    const ESTADO_AL_DIA = 'AL_DIA';
    const ESTADO_EN_PLAZO = 'EN_PLAZO';
    const ESTADO_MOROSO = 'MOROSO';
    const ESTADO_DEUDOR = 'DEUDOR';

    /**
     * Calcular estado de cobranza de un alumno.
     *
     * @param int $alumnoId
     * @param Carbon|null $fecha Fecha de referencia (default: hoy Buenos Aires)
     * @return array {estado, deudas_pendientes, deuda_mes_vigente, dias_gracia_restantes}
     */
    public function estadoAlumno(int $alumnoId, ?Carbon $fecha = null): array
    {
        $fecha = $fecha ?? Carbon::now();
        $alumno = Alumno::find($alumnoId);
        $deudas = DeudaCuota::where('alumno_id', $alumnoId)->get();
        $diasGracia = $this->diasGracia();
        $diasPlazoAlta = $this->diasPlazoAlta();

        $fechaAlta = !empty($alumno?->fecha_alta)
            ? Carbon::parse($alumno->fecha_alta)
            : null;

        $infoCuotas = $this->calcularEstadoDesdeDeudas(
            $deudas,
            $fecha,
            $diasGracia,
            $diasPlazoAlta,
            $fechaAlta,
            $this->recargosPendientesPorAlumno(collect([$alumnoId]))->get($alumnoId, collect())
        );

        $estadoFinal = $infoCuotas['estado'];
        $diasGraciaRestantes = $infoCuotas['dias_gracia_restantes'];

        if ($alumno) {
            $inscripciones = $this->inscripcionPendientePorAlumno(collect([$alumno]));
            $montoInscripcion = (float) ($inscripciones[$alumno->id] ?? 0.0);

            if ($montoInscripcion > 0) {
                $infoInscripcion = $this->calcularEstadoInscripcion($alumno, $fecha, $diasGracia, $diasPlazoAlta);
                $estadoFinal = $this->peorEstado($infoCuotas['estado'], $infoInscripcion['estado']);
                if ($estadoFinal === self::ESTADO_EN_PLAZO) {
                    $diasGraciaRestantes = max($infoCuotas['dias_gracia_restantes'], $infoInscripcion['dias_gracia_restantes']);
                } else {
                    $diasGraciaRestantes = 0;
                }
            }
        }

        return [
            'alumno_id' => $alumnoId,
            'estado' => $estadoFinal,
            'deudas_pendientes' => $infoCuotas['deudas_pendientes'],
            'deuda_mes_vigente' => $infoCuotas['deuda_mes_vigente'],
            'dias_gracia_restantes' => $diasGraciaRestantes,
        ];
    }

    /**
     * Calcular estados para una colección ya paginada sin consultas por alumno.
     *
     * @return array<int, string>
     */
    public function estadosParaAlumnos(Collection $alumnos, ?Carbon $fecha = null): array
    {
        $fecha = $fecha ?? Carbon::now();
        $alumnoIds = $alumnos->pluck('id');

        if ($alumnoIds->isEmpty()) {
            return [];
        }

        $deudasPorAlumno = DeudaCuota::whereIn('alumno_id', $alumnoIds)
            ->get()
            ->groupBy('alumno_id');
        $diasGracia = $this->diasGracia();
        $diasPlazoAlta = $this->diasPlazoAlta();
        $inscripcionesPorAlumno = $this->inscripcionPendientePorAlumno($alumnos);
        $recargosPorAlumno = $this->recargosPendientesPorAlumno($alumnoIds);

        return $alumnos->mapWithKeys(function (Alumno $alumno) use ($deudasPorAlumno, $inscripcionesPorAlumno, $recargosPorAlumno, $fecha, $diasGracia, $diasPlazoAlta) {
            $fechaAlta = !empty($alumno->fecha_alta)
                ? Carbon::parse($alumno->fecha_alta)
                : null;

            $info = $this->calcularEstadoDesdeDeudas(
                $deudasPorAlumno->get($alumno->id, collect()),
                $fecha,
                $diasGracia,
                $diasPlazoAlta,
                $fechaAlta,
                $recargosPorAlumno->get($alumno->id, collect())
            );

            $montoInscripcion = (float) ($inscripcionesPorAlumno[$alumno->id] ?? 0.0);
            if ($montoInscripcion > 0) {
                $infoInscripcion = $this->calcularEstadoInscripcion($alumno, $fecha, $diasGracia, $diasPlazoAlta);
                $estadoFinal = $this->peorEstado($info['estado'], $infoInscripcion['estado']);
            } else {
                $estadoFinal = $info['estado'];
            }

            return [$alumno->id => $estadoFinal];
        })->all();
    }

    /**
     * Cuánto debe cada alumno: cuotas impagas más la inscripción pendiente.
     *
     * A54 y A55: este es el **único** cálculo de saldo del sistema. Antes cada pantalla
     * lo sacaba por su cuenta y decían cosas distintas del mismo alumno — el selector de
     * cobro contaba como deudor a quien tenía una cuota PENDIENTE con saldo cero y se
     * olvidaba la inscripción. Si falta un dato acá, se agrega acá; no se calcula aparte.
     *
     * La inscripción es una por persona (por DNI), así que a quien está anotado en dos
     * deportes se le imputa en un solo registro, el primero, para no contarla dos veces.
     *
     * @param Collection<int, Alumno> $alumnos
     * @return array<int, array{cuotas: float, inscripcion: float, total: float, cuotas_impagas: int, mas_antigua: string}>
     */
    public function saldoDeAlumnos(Collection $alumnos): array
    {
        if ($alumnos->isEmpty()) {
            return [];
        }

        $alumnoIds = $alumnos->pluck('id');

        $deudasPorAlumno = DeudaCuota::whereIn('alumno_id', $alumnoIds)
            ->get()
            ->groupBy('alumno_id');

        $inscripcionPorAlumno = $this->inscripcionPendientePorAlumno($alumnos);
        $recargosPorAlumno = $this->recargosPendientesPorAlumno($alumnoIds);

        $necesitaFechas = $alumnos->contains(fn($a) => !array_key_exists('fecha_alta', $a->getAttributes()));
        $fechasAlta = $necesitaFechas
            ? Alumno::whereIn('id', $alumnoIds)->pluck('fecha_alta', 'id')
            : collect();

        $saldos = [];
        foreach ($alumnos as $alumno) {
            $cuotas = 0.0;
            $impagas = 0;
            $masAntigua = null;

            foreach ($deudasPorAlumno->get($alumno->id, collect()) as $deuda) {
                if (!$this->estaImpaga($deuda)) {
                    continue;
                }

                $cuotas += max(0, (float) $deuda->monto_original
                    - (float) $deuda->monto_pagado
                    - (float) $deuda->monto_condonado);
                $impagas++;

                if ($masAntigua === null || $deuda->periodo < $masAntigua) {
                    $masAntigua = $deuda->periodo;
                }
            }

            $inscripcion = (float) ($inscripcionPorAlumno[$alumno->id] ?? 0.0);
            $recargos = $recargosPorAlumno->get($alumno->id, collect());
            $punitorios = round((float) $recargos->sum('saldo_para_cobranza'), 2);
            foreach ($recargos as $cargo) {
                $periodo = $cargo->deudaCuota->periodo;
                if ($masAntigua === null || $periodo < $masAntigua) $masAntigua = $periodo;
            }

            // T31: Si debe inscripción, ordenar por antigüedad de esa deuda (fecha_alta Y-m)
            if ($inscripcion > 0) {
                $fechaAlta = $alumno->fecha_alta ?? $fechasAlta->get($alumno->id);
                if (!empty($fechaAlta)) {
                    $periodoInscripcion = Carbon::parse($fechaAlta)->format('Y-m');
                    if ($masAntigua === null || $periodoInscripcion < $masAntigua) {
                        $masAntigua = $periodoInscripcion;
                    }
                }
            }

            $saldos[$alumno->id] = [
                'cuotas' => round($cuotas, 2),
                'inscripcion' => $inscripcion,
                'punitorios' => $punitorios,
                'total' => round($cuotas + $inscripcion + $punitorios, 2),
                'cuotas_impagas' => $impagas,
                'mas_antigua' => $masAntigua ?? '9999-99',
            ];
        }

        return $saldos;
    }

    /**
     * Inscripción vigente que todavía no se cobró, por alumno.
     *
     * El cargo puede apuntar a un registro que no está en la colección —otro deporte de la
     * misma persona, o un registro inactivo—: en ese caso se imputa al primero de la
     * colección que tenga el mismo DNI.
     *
     * @param Collection<int, Alumno> $alumnos
     * @return array<int, float>
     */
    private function inscripcionPendientePorAlumno(Collection $alumnos): array
    {
        $cargos = \App\Models\CargoAlumno::where('tipo', 'INSCRIPCION')
            ->where('estado', 'VIGENTE')
            ->with('pagos')
            ->get();

        if ($cargos->isEmpty()) {
            return [];
        }

        $alumnoIdsCargos = $cargos->pluck('alumno_id')->filter()->unique();
        $alumnoIdsActivos = Alumno::whereIn('id', $alumnoIdsCargos)
            ->where('activo', true)
            ->pluck('id')
            ->flip();

        $pendientes = [];
        foreach ($cargos as $cargo) {
            $pendiente = max(0, (float) $cargo->monto_original
                - (float) $cargo->pagos->sum('pivot.monto_aplicado')
                - (float) $cargo->monto_condonado);

            if ($pendiente <= 0) {
                continue;
            }

            $destino = $alumnos->firstWhere('id', $cargo->alumno_id);

            if ($destino === null && !isset($alumnoIdsActivos[$cargo->alumno_id])) {
                $dniNorm = \App\Services\InscripcionService::dni($cargo->dni ?? '');
                $destino = $alumnos->first(
                    fn (Alumno $a) => \App\Services\InscripcionService::dni($a->dni ?? '') === $dniNorm
                );
            }

            if ($destino !== null) {
                $pendientes[$destino->id] = round($pendiente, 2);
            }
        }

        return $pendientes;
    }

    /** Recargos de su mes, incluso cuando el capital ya quedó pagado o condonado. */
    public function recargosPendientesPorAlumno(Collection $alumnoIds): Collection
    {
        if ($alumnoIds->isEmpty()) return collect();
        return CargoAlumno::where('tipo', 'PUNITORIO')->where('estado', 'VIGENTE')
            ->whereIn('alumno_id', $alumnoIds)
            ->whereHas('deudaCuota', fn ($q) => $q->where('periodo', '<=', now()->format('Y-m')))
            ->with(['pagos', 'deudaCuota'])->get()
            ->each(fn ($cargo) => $cargo->setAttribute('saldo_para_cobranza', round(max(0,
                (float) $cargo->monto_original - (float) $cargo->monto_condonado
                - (float) $cargo->pagos->sum('pivot.monto_aplicado')), 2)))
            ->filter(fn ($cargo) => $cargo->saldo_para_cobranza > 0)->groupBy('alumno_id');
    }

    /**
     * Filtrar alumnos activos por estado de cobranza computado.
     */
    public function filtrarAlumnosPorEstado(
        ?string $estadoCobranza = null,
        ?int $deporteId = null,
        ?int $grupoId = null
    ): Collection {
        $query = Alumno::where('activo', true)
            ->with([
                'deudaCuotas',
                'deporte',
                'grupo.deporte',
                'grupo.nivel',
                'planActivo.plan',
            ]);

        if ($deporteId) {
            $query->where('deporte_id', $deporteId);
        }
        if ($grupoId) {
            $query->where('grupo_id', $grupoId);
        }

        $alumnos = $query->get();
        $fecha = Carbon::now();
        $diasGracia = $this->diasGracia();
        $diasPlazoAlta = $this->diasPlazoAlta();
        $inscripcionesPorAlumno = $this->inscripcionPendientePorAlumno($alumnos);
        $recargosPorAlumno = $this->recargosPendientesPorAlumno($alumnos->pluck('id'));

        $resultado = $alumnos->map(function (Alumno $alumno) use ($inscripcionesPorAlumno, $recargosPorAlumno, $fecha, $diasGracia, $diasPlazoAlta) {
            $fechaAlta = !empty($alumno->fecha_alta)
                ? Carbon::parse($alumno->fecha_alta)
                : null;

            $info = $this->calcularEstadoDesdeDeudas(
                $alumno->deudaCuotas,
                $fecha,
                $diasGracia,
                $diasPlazoAlta,
                $fechaAlta,
                $recargosPorAlumno->get($alumno->id, collect())
            );

            $montoInscripcion = (float) ($inscripcionesPorAlumno[$alumno->id] ?? 0.0);
            if ($montoInscripcion > 0) {
                $infoInscripcion = $this->calcularEstadoInscripcion($alumno, $fecha, $diasGracia, $diasPlazoAlta);
                $estadoFinal = $this->peorEstado($info['estado'], $infoInscripcion['estado']);
            } else {
                $estadoFinal = $info['estado'];
            }

            $alumno->setAttribute('estado_cobranza', $estadoFinal);
            return $alumno;
        });

        if ($estadoCobranza) {
            $resultado = $resultado->filter(
                fn(Alumno $a) => $a->estado_cobranza === $estadoCobranza
            )->values();
        }

        return $resultado;
    }

    /**
     * Listado unificado por persona para la pantalla de cobranza.
     * Agrupa alumnos con el mismo DNI en una sola fila, totaliza deudas
     * y ordena por defecto a quienes deben (antigüedad de deuda asc).
     */
    public function listadoCobranza(
        ?string $estadoFiltro = null,
        ?int $deporteId = null,
        ?int $grupoId = null,
        ?Carbon $fecha = null
    ): Collection {
        $fecha = $fecha ?? Carbon::now();
        $diasGracia = $this->diasGracia();
        $diasPlazoAlta = $this->diasPlazoAlta();

        $todosAlumnos = Alumno::where('activo', true)
            ->with([
                'deudaCuotas',
                'deporte',
                'grupo.deporte',
                'grupo.nivel',
                'planActivo.plan',
            ])
            ->get();

        $saldos = $this->saldoDeAlumnos($todosAlumnos);
        $recargosPorAlumno = $this->recargosPendientesPorAlumno($todosAlumnos->pluck('id'));

        // Calcular estado y deuda para cada registro individual
        foreach ($todosAlumnos as $alumno) {
            $fechaAlta = !empty($alumno->fecha_alta)
                ? Carbon::parse($alumno->fecha_alta)
                : null;

            $info = $this->calcularEstadoDesdeDeudas(
                $alumno->deudaCuotas,
                $fecha,
                $diasGracia,
                $diasPlazoAlta,
                $fechaAlta,
                $recargosPorAlumno->get($alumno->id, collect())
            );
            $saldo = $saldos[$alumno->id];

            $montoInscripcion = (float) $saldo['inscripcion'];
            if ($montoInscripcion > 0) {
                $infoInscripcion = $this->calcularEstadoInscripcion($alumno, $fecha, $diasGracia, $diasPlazoAlta);
                $estadoFinal = $this->peorEstado($info['estado'], $infoInscripcion['estado']);
            } else {
                $estadoFinal = $info['estado'];
            }

            $alumno->setAttribute('estado_cobranza', $estadoFinal);
            $alumno->setAttribute('deuda_total', $saldo['total']);
            $alumno->setAttribute('deuda_cuotas', $saldo['cuotas']);
            $alumno->setAttribute('inscripcion_pendiente', $saldo['inscripcion']);
            $alumno->setAttribute('punitorios_pendientes', $saldo['punitorios']);
            $alumno->setAttribute('deuda_mas_antigua', $saldo['mas_antigua']);
            $alumno->setAttribute('cantidad_cuotas_impagas', $saldo['cuotas_impagas']);
        }

        // Calcular renglón de ayuda para registros que comparten DNI
        $alumnosPorDni = $todosAlumnos->groupBy(function (Alumno $a) {
            $dniNorm = \App\Services\InscripcionService::dni($a->dni ?? '');
            return !empty($dniNorm) ? $dniNorm : 'sin_dni_' . $a->id;
        });

        foreach ($todosAlumnos as $alumno) {
            $dniNorm = \App\Services\InscripcionService::dni($alumno->dni ?? '');
            $ayudaTexto = null;

            if (!empty($dniNorm) && $alumnosPorDni->has($dniNorm)) {
                $otros = $alumnosPorDni->get($dniNorm)->filter(fn(Alumno $o) => $o->id !== $alumno->id);
                $mensajes = [];
                foreach ($otros as $otro) {
                    if (($otro->deuda_total ?? 0) > 0) {
                        $depNombre = $otro->deporte->nombre ?? 'otro deporte';
                        $montoFmt = \App\Support\FormatoImporte::pesos($otro->deuda_total);
                        $mensajes[] = "{$montoFmt} en {$depNombre}";
                    }
                }
                if (!empty($mensajes)) {
                    $ayudaTexto = 'también debe ' . implode(', ', $mensajes);
                }
            }

            $alumno->setAttribute('ayuda_otro_deporte', $ayudaTexto);
        }

        $resultado = $todosAlumnos;

        // Filtros por deporte y grupo
        if ($deporteId) {
            $resultado = $resultado->filter(fn(Alumno $a) => $a->deporte_id == $deporteId);
        }
        if ($grupoId) {
            $resultado = $resultado->filter(fn(Alumno $a) => $a->grupo_id == $grupoId);
        }

        // Filtro por estado
        if ($estadoFiltro === 'TODOS') {
            // Mostrar todos los alumnos
        } elseif ($estadoFiltro && in_array($estadoFiltro, [self::ESTADO_AL_DIA, self::ESTADO_EN_PLAZO, self::ESTADO_MOROSO, self::ESTADO_DEUDOR])) {
            $resultado = $resultado->filter(fn(Alumno $a) => $a->estado_cobranza === $estadoFiltro);
        } else {
            // Default (o DEUDORES): solo deudores o morosos por cuotas vencidas
            $resultado = $resultado->filter(fn(Alumno $a) => in_array($a->estado_cobranza, [self::ESTADO_DEUDOR, self::ESTADO_MOROSO]));
        }

        // Ordenamiento por antigüedad de deuda impaga (más vieja primero), luego apellido y nombre
        return $resultado->sort(function (Alumno $a, Alumno $b) {
            $antiguedadA = $a->deuda_mas_antigua ?? '9999-99';
            $antiguedadB = $b->deuda_mas_antigua ?? '9999-99';

            if ($antiguedadA !== $antiguedadB) {
                return strcmp($antiguedadA, $antiguedadB);
            }

            $nombreCompletoA = $a->apellido . ' ' . $a->nombre;
            $nombreCompletoB = $b->apellido . ' ' . $b->nombre;
            return strcmp($nombreCompletoA, $nombreCompletoB);
        })->values();
    }

    /**
     * Resumen dashboard de cobranza.
     */
    public function resumenDashboard(?Carbon $fecha = null): array
    {
        $fecha = $fecha ?? Carbon::now();

        $alumnos = Alumno::where('activo', true)
            ->with(['deudaCuotas', 'deporte', 'grupo.deporte', 'grupo.nivel'])
            ->get();

        $conteos = [
            self::ESTADO_AL_DIA => 0,
            self::ESTADO_EN_PLAZO => 0,
            self::ESTADO_MOROSO => 0,
            self::ESTADO_DEUDOR => 0,
        ];

        $porDeporte = [];
        $porGrupo = [];
        $diasGracia = $this->diasGracia();
        $diasPlazoAlta = $this->diasPlazoAlta();
        $totalAdeudadoCuotas = 0.0;
        $inscripcionesPorAlumno = $this->inscripcionPendientePorAlumno($alumnos);
        $recargosPorAlumno = $this->recargosPendientesPorAlumno($alumnos->pluck('id'));

        foreach ($alumnos as $alumno) {
            $fechaAlta = !empty($alumno->fecha_alta)
                ? Carbon::parse($alumno->fecha_alta)
                : null;

            $info = $this->calcularEstadoDesdeDeudas(
                $alumno->deudaCuotas,
                $fecha,
                $diasGracia,
                $diasPlazoAlta,
                $fechaAlta,
                $recargosPorAlumno->get($alumno->id, collect())
            );

            $montoInscripcion = (float) ($inscripcionesPorAlumno[$alumno->id] ?? 0.0);
            if ($montoInscripcion > 0) {
                $infoInscripcion = $this->calcularEstadoInscripcion($alumno, $fecha, $diasGracia, $diasPlazoAlta);
                $estado = $this->peorEstado($info['estado'], $infoInscripcion['estado']);
            } else {
                $estado = $info['estado'];
            }

            $conteos[$estado]++;

            foreach ($alumno->deudaCuotas as $deuda) {
                if ($this->estaImpaga($deuda)) {
                    $totalAdeudadoCuotas += max(0, (float)$deuda->monto_original - (float)$deuda->monto_pagado - (float)$deuda->monto_condonado);
                }
            }

            // Por deporte
            $depId = $alumno->deporte_id;
            if (!isset($porDeporte[$depId])) {
                $porDeporte[$depId] = [
                    'deporte_id' => $depId,
                    'nombre' => $alumno->deporte->nombre ?? 'Sin deporte',
                    self::ESTADO_AL_DIA => 0,
                    self::ESTADO_EN_PLAZO => 0,
                    self::ESTADO_MOROSO => 0,
                    self::ESTADO_DEUDOR => 0,
                ];
            }
            $porDeporte[$depId][$estado]++;

            // Por grupo
            $grpId = $alumno->grupo_id;
            if (!isset($porGrupo[$grpId])) {
                $porGrupo[$grpId] = [
                    'grupo_id' => $grpId,
                    'nombre' => $alumno->grupo->nombre ?? 'Sin grupo',
                    self::ESTADO_AL_DIA => 0,
                    self::ESTADO_EN_PLAZO => 0,
                    self::ESTADO_MOROSO => 0,
                    self::ESTADO_DEUDOR => 0,
                ];
            }
            $porGrupo[$grpId][$estado]++;
        }

        // Inscripciones pendientes para alumnos activos
        $totalAdeudadoInscripciones = $this->totalInscripcionesPendientes($alumnos);

        $totalPunitorios = $recargosPorAlumno->flatten(1)->sum('saldo_para_cobranza');
        $totalAdeudado = round($totalAdeudadoCuotas + $totalAdeudadoInscripciones + $totalPunitorios, 2);

        return [
            'total_alumnos_activos' => $alumnos->count(),
            'total_adeudado' => $totalAdeudado,
            'por_estado' => $conteos,
            'por_deporte' => array_values($porDeporte),
            'por_grupo' => array_values($porGrupo),
        ];
    }

    /**
     * Total en pesos de inscripciones pendientes de alumnos activos.
     * Criterio canónico compartido con Cobranza (resumenDashboard) e Inicio (adminDashboard).
     *
     * @param Collection<int, Alumno>|null $alumnos
     * @return float
     */
    public function totalInscripcionesPendientes(?Collection $alumnos = null): float
    {
        $alumnos = $alumnos ?? Alumno::where('activo', true)->get(['id', 'dni']);
        $dnis = $alumnos->map(fn($a) => \App\Services\InscripcionService::dni($a->dni ?? ''))->filter()->unique();
        if ($dnis->isEmpty()) {
            return 0.0;
        }

        $cargosInscripcion = \App\Models\CargoAlumno::where('tipo', 'INSCRIPCION')
            ->where('estado', 'VIGENTE')
            ->whereIn('dni', $dnis)
            ->with('pagos')
            ->get();

        $total = 0.0;
        foreach ($cargosInscripcion as $cargo) {
            $pagado = (float) $cargo->pagos->sum('pivot.monto_aplicado');
            $condonado = (float) $cargo->monto_condonado;
            $orig = (float) $cargo->monto_original;
            $total += max(0, $orig - $pagado - $condonado);
        }

        return round($total, 2);
    }

    /**
     * Determinar si una deuda cuenta como "impaga".
     */
    private function estaImpaga(DeudaCuota $deuda): bool
    {
        // T18 (devolución de Gemini): un mes que todavía no empezó no es deuda. Puede quedar
        // guardado y pendiente si se anula un cobro adelantado; recién cuenta cuando empieza.
        return (float) $deuda->monto_pagado < (float) $deuda->monto_original
            && !in_array($deuda->estado, [DeudaCuota::ESTADO_PAGADA, DeudaCuota::ESTADO_CONDONADA])
            && $deuda->periodo <= now()->format('Y-m');
    }

    /**
     * Calcular estado desde una colección de deudas (para uso interno bulk).
     */
    private function calcularEstadoDesdeDeudas(
        Collection $deudas,
        Carbon $fecha,
        int $diasGracia,
        int $diasPlazoAlta,
        ?Carbon $fechaAlta = null,
        ?Collection $recargos = null
    ): array {
        $periodoVigente = $fecha->format('Y-m');
        $diaActual = (int) $fecha->format('d');
        $mesIngreso = $fechaAlta ? $fechaAlta->format('Y-m') : null;

        $limiteVencimientoAlta = $fechaAlta
            ? $this->limiteVencimientoAlta($fechaAlta, $diasGracia, $diasPlazoAlta)
            : null;

        $peorEstado = self::ESTADO_AL_DIA;
        $diasGraciaRestantes = 0;
        $deudaVigente = null;

        // Solo para clasificar el estado: el recargo nunca modifica el capital guardado.
        $deudasRecargos = ($recargos ?? collect())->map(fn ($cargo) => new DeudaCuota([
            'periodo' => $cargo->deudaCuota->periodo, 'monto_original' => $cargo->saldo_para_cobranza,
            'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]));
        foreach ($deudas->concat($deudasRecargos) as $deuda) {
            if (!$this->estaImpaga($deuda)) {
                continue;
            }

            if ($deuda->periodo === $periodoVigente && $deuda->exists) {
                $deudaVigente = $deuda;
            }

            $estadoDeuda = self::ESTADO_AL_DIA;
            $diasDeuda = 0;

            if ($deuda->periodo < $periodoVigente) {
                // Mes anterior/cerrado
                if ($mesIngreso && $deuda->periodo === $mesIngreso && $limiteVencimientoAlta) {
                    // Es la cuota del mes de ingreso y cruza de mes dentro del plazo de alta
                    if ($fecha->copy()->startOfDay()->lt($limiteVencimientoAlta)) {
                        $estadoDeuda = self::ESTADO_EN_PLAZO;
                        $diasDeuda = max(0, (int) $fecha->copy()->startOfDay()->diffInDays($limiteVencimientoAlta, false) - 1);
                    } else {
                        // Vencido el plazo con mes de ingreso cerrado: DEUDOR
                        $estadoDeuda = self::ESTADO_DEUDOR;
                    }
                } else {
                    $estadoDeuda = self::ESTADO_DEUDOR;
                }
            } elseif ($deuda->periodo === $periodoVigente) {
                // Mes corriente
                if ($mesIngreso && $deuda->periodo === $mesIngreso && $limiteVencimientoAlta) {
                    // Es la cuota del mes de ingreso dentro del mes corriente
                    if ($fecha->copy()->startOfDay()->lt($limiteVencimientoAlta)) {
                        $estadoDeuda = self::ESTADO_EN_PLAZO;
                        $diasDeuda = max(0, (int) $fecha->copy()->startOfDay()->diffInDays($limiteVencimientoAlta, false) - 1);
                    } else {
                        // Vencido el plazo mientras el mes sigue en curso: MOROSO
                        $estadoDeuda = self::ESTADO_MOROSO;
                    }
                } else {
                    // Cuota corriente estándar (mes posterior al alta o alta no especificada)
                    if ($diaActual > $diasGracia) {
                        $estadoDeuda = self::ESTADO_MOROSO;
                    } else {
                        $estadoDeuda = self::ESTADO_EN_PLAZO;
                        $diasDeuda = max(0, $diasGracia - $diaActual);
                    }
                }
            }

            $peorEstado = $this->peorEstado($peorEstado, $estadoDeuda);
            if ($estadoDeuda === self::ESTADO_EN_PLAZO) {
                $diasGraciaRestantes = max($diasGraciaRestantes, $diasDeuda);
            }
        }

        if ($peorEstado !== self::ESTADO_EN_PLAZO) {
            $diasGraciaRestantes = 0;
        }

        return [
            'estado' => $peorEstado,
            'deudas_pendientes' => $deudas
                ->filter(fn(DeudaCuota $deuda) => $this->estaImpaga($deuda))
                ->values(),
            'deuda_mes_vigente' => $deudaVigente,
            'dias_gracia_restantes' => $diasGraciaRestantes,
        ];
    }

    private function diasGracia(): int
    {
        $diasGracia = Configuracion::get('dias_gracia_cobranza');

        if (!is_int($diasGracia) || $diasGracia < 1) {
            throw new \LogicException('La configuración dias_gracia_cobranza no está definida correctamente.');
        }

        return $diasGracia;
    }

    private function diasPlazoAlta(): int
    {
        $diasPlazo = Configuracion::get('dias_plazo_alta');

        if (!is_int($diasPlazo) || $diasPlazo < 1) {
            throw new \LogicException('La configuración dias_plazo_alta no está definida correctamente.');
        }

        return $diasPlazo;
    }

    /**
     * Límite de vencimiento para deudas nacidas con el alta (inscripción y cuota del mes de ingreso).
     *
     * Rige el plazo propio del alumno nuevo (dias_plazo_alta) o el vencimiento general del mes de ingreso
     * (dias_gracia_cobranza), el que venza más tarde (el más favorable al alumno).
     */
    public function limiteDeAlta(Alumno $alumno): ?Carbon
    {
        return $alumno->fecha_alta
            ? $this->limiteVencimientoAlta(Carbon::parse($alumno->fecha_alta), $this->diasGracia(), $this->diasPlazoAlta())
            : null;
    }

    private function limiteVencimientoAlta(Carbon $fechaAlta, int $diasGracia, int $diasPlazoAlta): Carbon
    {
        $limitePlazoPropio = $fechaAlta->copy()->startOfDay()->addDays($diasPlazoAlta);
        $limiteGraciaMes = Carbon::create($fechaAlta->year, $fechaAlta->month, 1)->startOfDay()->addDays($diasGracia);

        return $limitePlazoPropio->gt($limiteGraciaMes) ? $limitePlazoPropio : $limiteGraciaMes;
    }

    /**
     * Calcular el estado de cobranza correspondiente a una inscripción impaga.
     *
     * Reglas de Carlos (12/10/2026 y 13/10/2026):
     * La inscripción impaga cuenta desde la fecha de ingreso del alumno (fecha_alta
     * del registro al que se le imputa):
     * - Dentro del plazo de alta (el más largo entre dias_plazo_alta y días de gracia general): EN_PLAZO.
     * - Vencido el plazo:
     *   - si el mes de ingreso sigue en curso: MOROSO;
     *   - si el mes de ingreso ya cerró: DEUDOR.
     *
     * @param Alumno $alumno
     * @param Carbon $fecha
     * @param int $diasGracia
     * @param int $diasPlazoAlta
     * @return array{estado: string, dias_gracia_restantes: int}
     */
    private function calcularEstadoInscripcion(
        Alumno $alumno,
        Carbon $fecha,
        int $diasGracia,
        int $diasPlazoAlta
    ): array {
        $fechaAlta = !empty($alumno->fecha_alta)
            ? Carbon::parse($alumno->fecha_alta)
            : $fecha;

        $mesIngreso = $fechaAlta->format('Y-m');
        $mesActual = $fecha->format('Y-m');

        $limiteVencimiento = $this->limiteVencimientoAlta($fechaAlta, $diasGracia, $diasPlazoAlta);

        if ($fecha->copy()->startOfDay()->lt($limiteVencimiento)) {
            $diasRestantes = max(0, (int) $fecha->copy()->startOfDay()->diffInDays($limiteVencimiento, false) - 1);
            return [
                'estado' => self::ESTADO_EN_PLAZO,
                'dias_gracia_restantes' => $diasRestantes,
            ];
        }

        $estado = ($mesIngreso < $mesActual)
            ? self::ESTADO_DEUDOR
            : self::ESTADO_MOROSO;

        return [
            'estado' => $estado,
            'dias_gracia_restantes' => 0,
        ];
    }

    /**
     * Determinar el peor estado entre dos según la jerarquía:
     * DEUDOR > MOROSO > EN_PLAZO > AL_DIA.
     */
    private function peorEstado(string $estadoA, string $estadoB): string
    {
        $jerarquia = [
            self::ESTADO_AL_DIA => 1,
            self::ESTADO_EN_PLAZO => 2,
            self::ESTADO_MOROSO => 3,
            self::ESTADO_DEUDOR => 4,
        ];

        $nivelA = $jerarquia[$estadoA] ?? 1;
        $nivelB = $jerarquia[$estadoB] ?? 1;

        return $nivelA >= $nivelB ? $estadoA : $estadoB;
    }
}
