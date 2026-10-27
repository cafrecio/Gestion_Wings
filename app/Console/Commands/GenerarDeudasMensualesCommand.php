<?php

namespace App\Console\Commands;

use App\Models\Alumno;
use App\Models\AlumnoRevisionCobranza;
use App\Models\Asistencia;
use App\Models\DeudaCuota;
use App\Models\GeneracionCuotas;
use App\Models\Pago;
use App\Services\AvisoAdminService;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerarDeudasMensualesCommand extends Command
{
    protected $signature = 'cobranza:generar-deudas
        {--periodo= : Período objetivo YYYY-MM (default: mes actual)}
        {--si-falta : Solo si las cuotas del mes no se generaron todavía (lo usa el programador, T40)}';

    protected $description = 'Genera deudas de cuota mensual para alumnos activos elegibles';

    private PagoCuotaService $pagoCuotaService;
    private AvisoAdminService $avisos;

    public function __construct(PagoCuotaService $pagoCuotaService, AvisoAdminService $avisos)
    {
        parent::__construct();
        $this->pagoCuotaService = $pagoCuotaService;
        $this->avisos = $avisos;
    }

    public function handle(): int
    {
        if (!$this->option('si-falta')) {
            return $this->generar(Carbon::now());
        }

        // T40 (DU-52): el programador lo corre todos los días. Si el día 1 no
        // corrió, el primer día en que el servidor esté arriba genera lo que
        // falta. Repetir no duplica: cada alumno con la cuota del mes se saltea.
        $ahora = Carbon::now();
        if ($this->option('periodo')) {
            $this->error('--si-falta trabaja siempre sobre el mes actual; no se combina con --periodo.');
            return Command::FAILURE;
        }
        if (!GeneracionCuotas::faltaGenerar($ahora)) {
            $this->info('Las cuotas de '.$ahora->format('m/Y').' ya están generadas o no corresponde generarlas hoy.');
            return Command::SUCCESS;
        }

        try {
            $resultado = $this->generar($ahora);
        } catch (\Throwable $e) {
            Log::error('No se pudieron generar las cuotas del mes.', [
                'periodo' => $ahora->format('Y-m'),
                'motivo' => $e->getMessage(),
            ]);
            $this->avisos->cuotasNoGeneradas($ahora);
            $this->error('No se pudieron generar las cuotas: '.$e->getMessage());
            return Command::FAILURE;
        }

        if ($resultado === Command::SUCCESS && $ahora->day > 1) {
            $this->avisos->cuotasGeneradasConAtraso(
                GeneracionCuotas::where('periodo', $ahora->format('Y-m'))->firstOrFail(),
                $ahora,
            );
        }

        return $resultado;
    }

    private function generar(Carbon $ahora): int
    {

        // Determinar período objetivo
        $periodoInput = $this->option('periodo');
        if ($periodoInput) {
            if (!preg_match('/^\d{4}-\d{2}$/', $periodoInput)) {
                $this->error("Formato de período inválido. Use YYYY-MM.");
                return Command::FAILURE;
            }
            $periodo = $periodoInput;
        } else {
            $periodo = $ahora->format('Y-m');
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
            $tieneAsistenciasMesAnterior = Asistencia::where('alumno_id', $alumno->id)
                ->where('presente', true)
                ->whereHas('clase', function ($query) use ($mesAnteriorInicio, $mesAnteriorFin) {
                    $query->whereBetween('fecha', [$mesAnteriorInicio, $mesAnteriorFin]);
                })
                ->exists();

            $limiteReciente = $ahora->copy()->subDays(15)->startOfDay();
            $altaReciente = $alumno->fecha_alta
                && $alumno->fecha_alta->betweenIncluded($limiteReciente, $ahora);
            $tienePagoReciente = $altaReciente && Pago::where('alumno_id', $alumno->id)
                ->where('estado', Pago::ESTADO_COMPLETADO)
                ->whereBetween('fecha_pago', [
                    $limiteReciente->toDateString(),
                    $ahora->toDateString(),
                ])
                ->exists();

            if ($tieneAsistenciasMesAnterior || $tienePagoReciente) {
                // Elegible: crear deuda con la tarifa histórica del período.
                $precio = $alumnoPlan->plan->precioParaPeriodo($periodo, $alumno->fecha_alta);
                $this->pagoCuotaService->crearDeudaSiNoExiste(
                    $alumno->id,
                    $periodo,
                    $precio
                );
                $contCreados++;
                $this->line("  Alumno #{$alumno->id} ({$alumno->nombre} {$alumno->apellido}): deuda creada - \${$precio}");
            } else {
                // No elegible: enviar a revisión. Solo cuenta como envío si la fila es nueva:
                // un alumno que ya estaba en Revisión de este mes no se vuelve a contar al
                // repetir la corrida (T92, devuelta por Codex: la constancia daba 1, 2, 4).
                $revision = AlumnoRevisionCobranza::firstOrCreate(
                    [
                        'alumno_id' => $alumno->id,
                        'periodo_objetivo' => $periodo,
                    ],
                    [
                        'motivo' => 'SIN_ASISTENCIAS_NI_ALTA_PAGO_RECIENTES',
                        'estado_revision' => AlumnoRevisionCobranza::ESTADO_PENDIENTE,
                    ]
                );
                if ($revision->wasRecentlyCreated) {
                    $contRevision++;
                    $this->warn("  Alumno #{$alumno->id} ({$alumno->nombre} {$alumno->apellido}): enviado a revisión.");
                } else {
                    $this->line("  Alumno #{$alumno->id} ({$alumno->nombre} {$alumno->apellido}): ya estaba en revisión.");
                }
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

        // Constancia de que el mes terminó (T40). Solo al final: si algo corta la
        // corrida a mitad, el mes sigue figurando como pendiente y se reintenta.
        $generacion = GeneracionCuotas::firstOrCreate(
            ['periodo' => $periodo],
            [
                'terminada_at' => Carbon::now(),
                'creadas' => $contCreados,
                'a_revision' => $contRevision,
                'ya_existian' => $contSkipped,
                'sin_plan' => $contSinPlan,
            ],
        );
        // Una corrida posterior del mismo mes (a mano, o repetida) no pisa la constancia
        // de la que generó el mes: suma lo que haya creado o mandado a Revisión ella y
        // actualiza los que siguen sin plan. Antes la pisaba: en la prueba del 1/11 la
        // constancia pasó de 100 creadas a 0 (PRU-04, día 23).
        if (!$generacion->wasRecentlyCreated) {
            GeneracionCuotas::whereKey($generacion->id)->update([
                'creadas' => DB::raw('creadas + '.(int) $contCreados),
                'a_revision' => DB::raw('a_revision + '.(int) $contRevision),
                'sin_plan' => $contSinPlan,
                // MariaDB declaró terminada_at con ON UPDATE CURRENT_TIMESTAMP: si no se
                // repite su valor, tocar la fila la cambiaría a la hora real del servidor.
                'terminada_at' => $generacion->terminada_at,
                'updated_at' => Carbon::now(),
            ]);
        }

        return Command::SUCCESS;
    }
}
