<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'clase_id',
        'alumno_id',
        'presente',
    ];

    protected $casts = [
        'presente' => 'boolean',
    ];

    /**
     * Relación con Clase
     */
    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }

    /**
     * Relación con Alumno
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    public function exceso(): HasOne
    {
        return $this->hasOne(AsistenciaExceso::class);
    }
}
