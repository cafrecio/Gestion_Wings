<?php

namespace App\Console\Commands;

use App\Models\Alumno;
use App\Models\AlumnoRevisionCobranza;
use App\Models\Asistencia;
use App\Models\DeudaCuota;
use App\Models\Pago;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerarDeudasMensualesCommand extends Command
{
    protected $signature = 'cobranza:generar-deudas
        {--periodo= : Período objetivo YYYY-MM (default: mes siguiente al actual)}';

    protected $description = 'Genera deudas de cuota mensual para alumnos activos elegibles';

    private PagoCuotaService $pagoCuotaService;

    public function __construct(PagoCuotaService $pagoCuotaService)
    {
        parent::__construct();
        $this->pagoCuotaService = $pagoCuotaService;
    }

    public function handle(): int
    {
        $ahora = Carbon::now('America/Argentina/Buenos_Aires');

        // Determinar período objetivo
        $periodoInput = $this->option('periodo');
        if ($periodoInput) {
            if (!preg_match('/^\d{4}-\d{2}$/', $periodoInput)) {
                $this->error("Formato de período inválido. Use YYYY-MM.");
                return Command::FAILURE;
            }
            $periodo = $periodoInput;
        } else {
            $periodo = $ahora->copy()->addMonth()->format('Y-m');
        }

        // Mes anterior al período objetivo (para verificar asistencias)
        $periodoAnterior = Carbon::parse($periodo . '-01')->subMonth();
        $mesAnteriorInicio = $periodoAnterior->copy()->startOfMonth()->toDateString();
        $mesAnteriorFin = $periodoAnterior->copy()->endOfMonth()->toDateString();

        $this->info("=== Generación de deudas para período: {$periodo} ===");
        $this->info("Verificando asistencias del mes anterior: {$periodoAnterior->format('Y-m')}");

        // Obtener alumnos activos con plan activo
        $alumnos = Alumno::where('activo', true)
            ->with(['planActivo.plan'])
            ->get();

        $contCreados = 0;
        $contRevision = 0;
        $contSkipped = 0;
        $contSinPlan = 0;

        foreach ($alumnos as $alumno) {
            // Skip si ya existe deuda para este período
            $deudaExistente = DeudaCuota::where('alumno_id', $alumno->id)
                ->where('periodo', $periodo)
                ->exists();

            if ($deudaExistente) {
                $contSkipped++;
                continue;
            }

            // Obtener plan aplicable al período
            $alumnoPlan = $this->pagoCuotaService->obtenerPlanParaPeriodo($alumno->id, $periodo);

            if (!$alumnoPlan || !$alumnoPlan->plan) {
                $contSinPlan++;
                $this->warn("  Alumno #{$alumno->id} ({$alumno->nombre} {$alumno->apellido}): sin plan aplicable, omitido.");
                continue;
            }

            // Verificar elegibilidad estricta contra el período anterior
            $mesAnteriorMes = $periodoAnterior->month;
            $mesAnteriorAnio = $periodoAnterior->year;

            $tieneAsistenciasMesAnterior = Asistencia::where('alumno_id', $alumno->id)
                ->where('presente', true)
                ->whereHas('clase', function ($query) use ($mesAnteriorInicio, $mesAnteriorFin) {
                    $query->whereBetween('fecha', [$mesAnteriorInicio, $mesAnteriorFin]);
                })
                ->exists();

            $tienePagoPeriodoAnterior = Pago::where('alumno_id', $alumno->id)
                ->where('mes', $mesAnteriorMes)
                ->where('anio', $mesAnteriorAnio)
                ->exists();

            if ($tieneAsistenciasMesAnterior || $tienePagoPeriodoAnterior) {
                // Elegible: crear deuda
                $this->pagoCuotaService->crearDeudaSiNoExiste(
                    $alumno->id,
                    $periodo,
                    $alumnoPlan->plan->precio_mensual
                );
                $contCreados++;
                $this->line("  Alumno #{$alumno->id} ({$alumno->nombre} {$alumno->apellido}): deuda creada - \${$alumnoPlan->plan->precio_mensual}");
            } else {
                // No elegible: enviar a revisión
                AlumnoRevisionCobranza::firstOrCreate(
                    [
                        'alumno_id' => $alumno->id,
                        'periodo_objetivo' => $periodo,
                    ],
                    [
                        'motivo' => 'SIN_ASISTENCIAS_NI_PAGO_PERIODO_ANTERIOR',
                        'estado_revision' => AlumnoRevisionCobranza::ESTADO_PENDIENTE,
                    ]
                );
                $contRevision++;
                $this->warn("  Alumno #{$alumno->id} ({$alumno->nombre} {$alumno->apellido}): enviado a revisión.");
            }
        }

        $this->newLine();
        $this->info("=== Resumen ===");
        $this->info("Total alumnos activos: {$alumnos->count()}");
        $this->info("Deudas creadas: {$contCreados}");
        $this->info("Enviados a revisión: {$contRevision}");
        $this->info("Ya existían (skipped): {$contSkipped}");
        if ($contSinPlan > 0) {
            $this->warn("Sin plan aplicable: {$contSinPlan}");
        }

        return Command::SUCCESS;
    }
}
