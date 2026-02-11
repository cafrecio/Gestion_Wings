<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\AlumnoRevisionCobranza;
use App\Models\DeudaCuota;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CobranzaEstadoService
{
    const ESTADO_AL_DIA = 'AL_DIA';
    const ESTADO_MOROSO = 'MOROSO';
    const ESTADO_DEUDOR = 'DEUDOR';
    const DIA_GRACIA = 10;

    private PagoCuotaService $pagoCuotaService;

    public function __construct(PagoCuotaService $pagoCuotaService)
    {
        $this->pagoCuotaService = $pagoCuotaService;
    }

    /**
     * Calcular estado de cobranza de un alumno.
     *
     * @param int $alumnoId
     * @param Carbon|null $fecha Fecha de referencia (default: hoy Buenos Aires)
     * @return array {estado, deudas_pendientes, deuda_mes_vigente, dias_gracia_restantes}
     */
    public function estadoAlumno(int $alumnoId, ?Carbon $fecha = null): array
    {
        $fecha = $fecha ?? Carbon::now('America/Argentina/Buenos_Aires');
        $periodoVigente = $fecha->format('Y-m');
        $diaActual = (int) $fecha->format('d');

        $deudas = DeudaCuota::where('alumno_id', $alumnoId)->get();

        // Separar deudas impagas anteriores al mes vigente
        $impagasAnteriores = $deudas->filter(function (DeudaCuota $d) use ($periodoVigente) {
            return $d->periodo < $periodoVigente && $this->estaImpaga($d);
        });

        // Deuda del mes vigente
        $deudaVigente = $deudas->firstWhere('periodo', $periodoVigente);
        $vigentePagada = $deudaVigente ? !$this->estaImpaga($deudaVigente) : false;

        // Calcular estado
        if ($impagasAnteriores->isNotEmpty()) {
            $estado = self::ESTADO_DEUDOR;
        } elseif (!$vigentePagada && $deudaVigente && $diaActual > self::DIA_GRACIA) {
            $estado = self::ESTADO_MOROSO;
        } else {
            $estado = self::ESTADO_AL_DIA;
        }

        // Días de gracia restantes
        $diasGraciaRestantes = 0;
        if ($deudaVigente && $this->estaImpaga($deudaVigente) && $diaActual <= self::DIA_GRACIA) {
            $diasGraciaRestantes = self::DIA_GRACIA - $diaActual;
        }

        // Todas las deudas pendientes (impagas)
        $deudasPendientes = $deudas->filter(fn(DeudaCuota $d) => $this->estaImpaga($d))->values();

        return [
            'alumno_id' => $alumnoId,
            'estado' => $estado,
            'deudas_pendientes' => $deudasPendientes,
            'deuda_mes_vigente' => $deudaVigente,
            'dias_gracia_restantes' => $diasGraciaRestantes,
        ];
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
            ->with(['deudaCuotas', 'deporte', 'grupo', 'planActivo.plan']);

        if ($deporteId) {
            $query->where('deporte_id', $deporteId);
        }
        if ($grupoId) {
            $query->where('grupo_id', $grupoId);
        }

        $alumnos = $query->get();
        $fecha = Carbon::now('America/Argentina/Buenos_Aires');

        $resultado = $alumnos->map(function (Alumno $alumno) use ($fecha) {
            $info = $this->calcularEstadoDesdeDeudas($alumno->deudaCuotas, $fecha);
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
        $fecha = $fecha ?? Carbon::now('America/Argentina/Buenos_Aires');

        $alumnos = Alumno::where('activo', true)
            ->with(['deudaCuotas', 'deporte', 'grupo'])
            ->get();

        $conteos = [
            self::ESTADO_AL_DIA => 0,
            self::ESTADO_MOROSO => 0,
            self::ESTADO_DEUDOR => 0,
        ];

        $porDeporte = [];
        $porGrupo = [];

        foreach ($alumnos as $alumno) {
            $info = $this->calcularEstadoDesdeDeudas($alumno->deudaCuotas, $fecha);
            $estado = $info['estado'];
            $conteos[$estado]++;

            // Por deporte
            $depId = $alumno->deporte_id;
            if (!isset($porDeporte[$depId])) {
                $porDeporte[$depId] = [
                    'deporte_id' => $depId,
                    'nombre' => $alumno->deporte->nombre ?? 'Sin deporte',
                    self::ESTADO_AL_DIA => 0,
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
     * Resolver un item de la cola de revisión.
     *
     * @param int $revisionId
     * @param string $accion GENERAR_DEUDA | MARCAR_INACTIVO
     * @return AlumnoRevisionCobranza
     */
    public function resolverRevision(int $revisionId, string $accion): AlumnoRevisionCobranza
    {
        return DB::transaction(function () use ($revisionId, $accion) {
            $revision = AlumnoRevisionCobranza::findOrFail($revisionId);

            if ($revision->estado_revision !== AlumnoRevisionCobranza::ESTADO_PENDIENTE) {
                throw new \Exception("Esta revisión ya fue resuelta.");
            }

            switch ($accion) {
                case 'GENERAR_DEUDA':
                    $alumnoPlan = $this->pagoCuotaService->obtenerPlanParaPeriodo(
                        $revision->alumno_id,
                        $revision->periodo_objetivo
                    );

                    if (!$alumnoPlan || !$alumnoPlan->plan) {
                        throw new \Exception(
                            "Alumno sin plan aplicable para el período {$revision->periodo_objetivo}."
                        );
                    }

                    $this->pagoCuotaService->crearDeudaSiNoExiste(
                        $revision->alumno_id,
                        $revision->periodo_objetivo,
                        $alumnoPlan->plan->precio_mensual
                    );
                    break;

                case 'MARCAR_INACTIVO':
                    Alumno::where('id', $revision->alumno_id)
                        ->update(['activo' => false]);
                    break;

                default:
                    throw new \Exception("Acción no válida: {$accion}");
            }

            $revision->estado_revision = AlumnoRevisionCobranza::ESTADO_RESUELTO;
            $revision->save();

            return $revision;
        });
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
    private function calcularEstadoDesdeDeudas(Collection $deudas, Carbon $fecha): array
    {
        $periodoVigente = $fecha->format('Y-m');
        $diaActual = (int) $fecha->format('d');

        $impagasAnteriores = $deudas->filter(function (DeudaCuota $d) use ($periodoVigente) {
            return $d->periodo < $periodoVigente && $this->estaImpaga($d);
        });

        $deudaVigente = $deudas->firstWhere('periodo', $periodoVigente);
        $vigentePagada = $deudaVigente ? !$this->estaImpaga($deudaVigente) : false;

        if ($impagasAnteriores->isNotEmpty()) {
            $estado = self::ESTADO_DEUDOR;
        } elseif (!$vigentePagada && $deudaVigente && $diaActual > self::DIA_GRACIA) {
            $estado = self::ESTADO_MOROSO;
        } else {
            $estado = self::ESTADO_AL_DIA;
        }

        return ['estado' => $estado];
    }
}
