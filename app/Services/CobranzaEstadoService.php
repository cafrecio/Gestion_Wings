<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Configuracion;
use App\Models\DeudaCuota;
use App\Models\Pago;
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
        $tienePagos = Pago::where('alumno_id', $alumnoId)
            ->where('estado', Pago::ESTADO_COMPLETADO)
            ->exists();

        return array_merge([
            'alumno_id' => $alumnoId,
        ], $this->calcularEstadoDesdeDeudas(
            $deudas,
            $tienePagos,
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
        $alumnosConPagos = Pago::whereIn('alumno_id', $alumnoIds)
            ->where('estado', Pago::ESTADO_COMPLETADO)
            ->distinct()
            ->pluck('alumno_id')
            ->flip();
        $diasGracia = $this->diasGracia();

        return $alumnos->mapWithKeys(function (Alumno $alumno) use ($deudasPorAlumno, $alumnosConPagos, $fecha, $diasGracia) {
            $info = $this->calcularEstadoDesdeDeudas(
                $deudasPorAlumno->get($alumno->id, collect()),
                $alumnosConPagos->has($alumno->id),
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
            ->with(['deudaCuotas', 'deporte', 'grupo', 'planActivo.plan'])
            ->withExists([
                'pagos as tiene_pagos_registrados' => fn($q) => $q
                    ->where('estado', Pago::ESTADO_COMPLETADO),
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
                (bool) $alumno->tiene_pagos_registrados,
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
     * Resumen dashboard de cobranza.
     */
    public function resumenDashboard(?Carbon $fecha = null): array
    {
        $fecha = $fecha ?? Carbon::now();

        $alumnos = Alumno::where('activo', true)
            ->with(['deudaCuotas', 'deporte', 'grupo'])
            ->withExists([
                'pagos as tiene_pagos_registrados' => fn($q) => $q
                    ->where('estado', Pago::ESTADO_COMPLETADO),
            ])
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

        foreach ($alumnos as $alumno) {
            $info = $this->calcularEstadoDesdeDeudas(
                $alumno->deudaCuotas,
                (bool) $alumno->tiene_pagos_registrados,
                $fecha,
                $diasGracia
            );
            $estado = $info['estado'];
            $conteos[$estado]++;

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

        return [
            'total_alumnos_activos' => $alumnos->count(),
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
        bool $tienePagos,
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

        if (!$tienePagos || $impagasAnteriores->isNotEmpty()) {
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
