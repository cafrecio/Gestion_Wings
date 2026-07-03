<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumnoRevisionCobranza extends Model
{
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_RESUELTO  = 'RESUELTO';

    const RESOLUCION_CONTINUA        = 'CONTINUA';
    const RESOLUCION_INACTIVO        = 'INACTIVO';
    const RESOLUCION_REACTIVADO_AUTO = 'REACTIVADO_AUTO';

    protected $table = 'alumnos_revision_cobranza';

    protected $fillable = [
        'alumno_id',
        'periodo_objetivo',
        'motivo',
        'estado_revision',
        'resolucion',
        'nota_resolucion',
        'usuario_resolucion_id',
        'resuelto_at',
    ];

    protected $casts = [
        'resuelto_at' => 'datetime',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    public function usuarioResolucion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_resolucion_id');
    }

    /**
     * Marca como REACTIVADO_AUTO si hay una revisión pendiente para este alumno/período.
     * Se llama desde PagoCuotaService (pago) y AsistenciaController (asistencia).
     */
    public static function reactivarAuto(int $alumnoId, string $periodo): void
    {
        self::where('alumno_id', $alumnoId)
            ->where('periodo_objetivo', $periodo)
            ->where('estado_revision', self::ESTADO_PENDIENTE)
            ->update([
                'estado_revision' => self::ESTADO_RESUELTO,
                'resolucion'      => self::RESOLUCION_REACTIVADO_AUTO,
                'resuelto_at'     => now(),
            ]);
    }
}
