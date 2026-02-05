<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo pivot para la relación Clase-Profesor
 * Permite acceder a los datos de la tabla pivot como modelo
 */
class ClaseProfesor extends Model
{
    protected $table = 'clase_profesor';

    protected $fillable = [
        'clase_id',
        'profesor_id',
    ];

    /**
     * Relación con Clase
     */
    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }

    /**
     * Relación con Profesor
     */
    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Profesor::class);
    }
}
