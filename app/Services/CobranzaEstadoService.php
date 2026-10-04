<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Configuracion;
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
        $deudas = DeudaCuota::where('alumno_id', $alumnoId)->get();

        return array_merge([
            'alumno_id' => $alumnoId,
        ], $this->calcularEstadoDesdeDeudas(
            $deudas,
            $fecha,
            $this->diasGracia()
        ));
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

        return $alumnos->mapWithKeys(function (Alumno $alumno) use ($deudasPorAlumno, $fecha, $diasGracia) {
            $info = $this->calcularEstadoDesdeDeudas(
                $deudasPorAlumno->get($alumno->id, collect()),
                $fecha,
                $diasGracia
            );

            return [$alumno->id => $info['estado']];
        })->all();
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

        $resultado = $alumnos->map(function (Alumno $alumno) use ($fecha, $diasGracia) {
            $info = $this->calcularEstadoDesdeDeudas(
                $alumno->deudaCuotas,
                $fecha,
                $diasGracia
            );
            $alumno->setAttribute('estado_cobranza', $info['estado']);
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

        $todosAlumnos = Alumno::where('activo', true)
            ->with([
                'deudaCuotas',
                'deporte',
                'grupo.deporte',
                'grupo.nivel',
                'planActivo.plan',
            ])
            ->get();

        // Cargar inscripciones vigentes y mapear a alumno_id
        $cargos = \App\Models\CargoAlumno::where('tipo', 'INSCRIPCION')
            ->where('estado', 'VIGENTE')
            ->with('pagos')
            ->get();

        $cargosPorAlumno = [];
        foreach ($cargos as $cargo) {
            $pagado = (float)$cargo->pagos->sum('pivot.monto_aplicado');
            $condonado = (float)$cargo->monto_condonado;
            $orig = (float)$cargo->monto_original;
            $pendiente = max(0, $orig - $pagado - $condonado);
            if ($pendiente <= 0) continue;

            if ($todosAlumnos->contains('id', $cargo->alumno_id)) {
                $cargosPorAlumno[$cargo->alumno_id] = round($pendiente, 2);
            } else {
                $dniNorm = \App\Services\InscripcionService::dni($cargo->dni ?? '');
                $primerActivo = $todosAlumnos->first(function ($a) use ($dniNorm) {
                    return \App\Services\InscripcionService::dni($a->dni ?? '') === $dniNorm;
                });
                if ($primerActivo) {
                    $cargosPorAlumno[$primerActivo->id] = round($pendiente, 2);
                }
            }
        }

        // Calcular estado y deuda para cada registro individual
        foreach ($todosAlumnos as $alumno) {
            $info = $this->calcularEstadoDesdeDeudas(
                $alumno->deudaCuotas,
                $fecha,
                $diasGracia
            );
            $estadoAlumno = $info['estado'];

            $totalDeudaCuotas = 0.0;
            $cantidadCuotasImpagas = 0;
            $deudaMasAntigua = null;

            foreach ($alumno->deudaCuotas as $deuda) {
                if ($this->estaImpaga($deuda)) {
                    $saldo = max(0, (float)$deuda->monto_original - (float)$deuda->monto_pagado - (float)$deuda->monto_condonado);
                    $totalDeudaCuotas += $saldo;
                    $cantidadCuotasImpagas++;

                    if ($deudaMasAntigua === null || $deuda->periodo < $deudaMasAntigua) {
                        $deudaMasAntigua = $deuda->periodo;
                    }
                }
            }

            $inscripcionPendiente = (float)($cargosPorAlumno[$alumno->id] ?? 0.0);
            $deudaTotal = round($totalDeudaCuotas + $inscripcionPendiente, 2);

            $alumno->setAttribute('estado_cobranza', $estadoAlumno);
            $alumno->setAttribute('deuda_total', $deudaTotal);
            $alumno->setAttribute('deuda_cuotas', round($totalDeudaCuotas, 2));
            $alumno->setAttribute('inscripcion_pendiente', $inscripcionPendiente);
            $alumno->setAttribute('deuda_mas_antigua', $deudaMasAntigua ?? '9999-99');
            $alumno->setAttribute('cantidad_cuotas_impagas', $cantidadCuotasImpagas);
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
                        $montoFmt = '$ ' . number_format($otro->deuda_total, 2, ',', '.');
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
        $totalAdeudadoCuotas = 0.0;

        foreach ($alumnos as $alumno) {
            $info = $this->calcularEstadoDesdeDeudas(
                $alumno->deudaCuotas,
                $fecha,
                $diasGracia
            );
            $estado = $info['estado'];
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
        $dnis = $alumnos->map(fn($a) => \App\Services\InscripcionService::dni($a->dni))->filter()->unique();
        $totalAdeudadoInscripciones = 0.0;
        if ($dnis->isNotEmpty()) {
            $cargosInscripcion = \App\Models\CargoAlumno::where('tipo', 'INSCRIPCION')
                ->where('estado', 'VIGENTE')
                ->whereIn('dni', $dnis)
                ->with('pagos')
                ->get();
            foreach ($cargosInscripcion as $cargo) {
                $pagado = (float)$cargo->pagos->sum('pivot.monto_aplicado');
                $condonado = (float)$cargo->monto_condonado;
                $orig = (float)$cargo->monto_original;
                $totalAdeudadoInscripciones += max(0, $orig - $pagado - $condonado);
            }
        }

        $totalAdeudado = round($totalAdeudadoCuotas + $totalAdeudadoInscripciones, 2);

        return [
            'total_alumnos_activos' => $alumnos->count(),
            'total_adeudado' => $totalAdeudado,
            'por_estado' => $conteos,
            'por_deporte' => array_values($porDeporte),
            'por_grupo' => array_values($porGrupo),
        ];
    }

    /**
     * Determinar si una deuda cuenta como "impaga".
     */
    private function estaImpaga(DeudaCuota $deuda): bool
    {
        return (float) $deuda->monto_pagado < (float) $deuda->monto_original
            && !in_array($deuda->estado, [DeudaCuota::ESTADO_PAGADA, DeudaCuota::ESTADO_CONDONADA]);
    }

    /**
     * Calcular estado desde una colección de deudas (para uso interno bulk).
     */
    private function calcularEstadoDesdeDeudas(
        Collection $deudas,
        Carbon $fecha,
        int $diasGracia
    ): array
    {
        $periodoVigente = $fecha->format('Y-m');
        $diaActual = (int) $fecha->format('d');

        $impagasAnteriores = $deudas->filter(function (DeudaCuota $d) use ($periodoVigente) {
            return $d->periodo < $periodoVigente && $this->estaImpaga($d);
        });

        $deudaVigente = $deudas->firstWhere('periodo', $periodoVigente);
        $vigenteImpaga = $deudaVigente && $this->estaImpaga($deudaVigente);

        if ($impagasAnteriores->isNotEmpty()) {
            $estado = self::ESTADO_DEUDOR;
        } elseif ($vigenteImpaga && $diaActual > $diasGracia) {
            $estado = self::ESTADO_MOROSO;
        } elseif ($vigenteImpaga) {
            $estado = self::ESTADO_EN_PLAZO;
        } else {
            $estado = self::ESTADO_AL_DIA;
        }

        return [
            'estado' => $estado,
            'deudas_pendientes' => $deudas
                ->filter(fn(DeudaCuota $deuda) => $this->estaImpaga($deuda))
                ->values(),
            'deuda_mes_vigente' => $deudaVigente,
            'dias_gracia_restantes' => $estado === self::ESTADO_EN_PLAZO
                ? max(0, $diasGracia - $diaActual)
                : 0,
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
}
