<?php

namespace App\Services;

use App\Models\AlumnoRevisionCobranza;
use App\Models\DeudaCuota;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevisionCobranzaService
{
    public function __construct(private PagoCuotaService $pagoCuotaService) {}

    /**
     * Resolver manualmente una revisión pendiente.
     * CONTINUA: genera la deuda del período.
     * INACTIVO: inactiva el alumno + cancela deuda del período inmediato anterior.
     */
    public function resolver(int $revisionId, string $resolucion, ?string $nota, int $userId): AlumnoRevisionCobranza
    {
        $revision = AlumnoRevisionCobranza::with('alumno.planActivo.plan')->findOrFail($revisionId);

        if ($revision->estado_revision !== AlumnoRevisionCobranza::ESTADO_PENDIENTE) {
            throw new \Exception('Esta revisión ya fue resuelta.');
        }

        DB::transaction(function () use ($revision, $resolucion, $nota, $userId) {
            $revision->update([
                'estado_revision'      => AlumnoRevisionCobranza::ESTADO_RESUELTO,
                'resolucion'           => $resolucion,
                'nota_resolucion'      => $nota,
                'usuario_resolucion_id' => $userId,
                'resuelto_at'          => now(),
            ]);

            if ($resolucion === AlumnoRevisionCobranza::RESOLUCION_CONTINUA) {
                $alumnoPlan = $revision->alumno->planActivo;
                if ($alumnoPlan && $alumnoPlan->plan) {
                    $this->pagoCuotaService->crearDeudaSiNoExiste(
                        $revision->alumno_id,
                        $revision->periodo_objetivo,
                        $alumnoPlan->plan->precio_mensual
                    );
                }
            } elseif ($resolucion === AlumnoRevisionCobranza::RESOLUCION_INACTIVO) {
                $revision->alumno->update(['activo' => false]);

                // Cancelar deuda del período inmediato anterior si aún está pendiente
                $periodoAnterior = Carbon::parse($revision->periodo_objetivo . '-01')
                    ->subMonth()
                    ->format('Y-m');

                DeudaCuota::where('alumno_id', $revision->alumno_id)
                    ->where('periodo', $periodoAnterior)
                    ->where('estado', DeudaCuota::ESTADO_PENDIENTE)
                    ->update([
                        'estado'       => DeudaCuota::ESTADO_CONDONADA,
                        'observaciones' => 'Cancelada al inactivar alumno (revisión #' . $revision->id . ')',
                    ]);
            }
        });

        return $revision->fresh();
    }

    /**
     * Devuelve la cantidad de revisiones pendientes (para mostrar badge en sidebar).
     */
    public function contarPendientes(): int
    {
        return AlumnoRevisionCobranza::where('estado_revision', AlumnoRevisionCobranza::ESTADO_PENDIENTE)->count();
    }
}
