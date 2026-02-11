<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaExceso extends Model
{
    const MOTIVO_EXTRA = 'EXTRA';
    const MOTIVO_RECUPERA = 'RECUPERA';

    protected $table = 'asistencia_excesos';

    protected $fillable = [
        'asistencia_id',
        'alumno_id',
        'fecha_clase',
        'motivo',
        'detalle',
    ];

    protected $casts = [
        'fecha_clase' => 'date',
    ];

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }
}
