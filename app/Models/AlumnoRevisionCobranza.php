<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumnoRevisionCobranza extends Model
{
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_RESUELTO = 'RESUELTO';

    protected $table = 'alumnos_revision_cobranza';

    protected $fillable = [
        'alumno_id',
        'periodo_objetivo',
        'motivo',
        'estado_revision',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }
}
