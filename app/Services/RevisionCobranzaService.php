<?php

namespace App\Services;

use App\Models\AlumnoRevisionCobranza;
use Illuminate\Support\Facades\DB;

class RevisionCobranzaService
{
    public function __construct(private PagoCuotaService $pagoCuotaService) {}

    /**
     * Resolver manualmente una revisión pendiente.
     * CONTINUA: genera la deuda del período.
     * INACTIVO: inactiva el alumno. Nada más.
     *
     * La cola existe para una sola pregunta: ¿a este alumno le generamos la deuda
     * del mes o no? Es un proceso hacia adelante y no revisa lo ya generado.
     *
     * Hasta el 19/09/2026 INACTIVO además condonaba la deuda pendiente del mes
     * anterior. Esa deuda se generó el mes pasado porque en su momento cumplía
     * las condiciones, y esa decisión ya está tomada: darlo de baja hoy no la
     * vuelve inexistente. Condonar es una decisión aparte del ADMIN, con motivo,
     * para el alumno que no pudo asistir. Acá se hacía sola y sin que nadie la
     * pidiera. Criterio de Carlos, 19/09.
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
                if (!$alumnoPlan || !$alumnoPlan->plan) {
                    throw new \Exception('El alumno no tiene un plan activo. Asigná un plan antes de resolver como Continúa.');
                }
                $this->pagoCuotaService->crearDeudaSiNoExiste(
                    $revision->alumno_id,
                    $revision->periodo_objetivo,
                    $alumnoPlan->plan->precio_mensual
                );
            } elseif ($resolucion === AlumnoRevisionCobranza::RESOLUCION_INACTIVO) {
                // Solo se lo da de baja: no se le genera la deuda del período y
                // el historial queda como está. Lo que deba de meses anteriores
                // lo condona el ADMIN por su cuenta si corresponde.
                $revision->alumno->update(['activo' => false]);
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
