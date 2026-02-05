<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumnoPlan extends Model
{
    protected $table = 'alumno_planes';

    protected $fillable = [
        'alumno_id',
        'plan_id',
        'fecha_desde',
        'fecha_hasta',
        'activo',
    ];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Boot del modelo para validar regla de unicidad
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($alumnoPlan) {
            // Si se marca como activo, desactivar el plan activo anterior
            if ($alumnoPlan->activo) {
                self::where('alumno_id', $alumnoPlan->alumno_id)
                    ->where('activo', true)
                    ->update(['activo' => false, 'fecha_hasta' => now()]);
            }
        });
    }

    /**
     * Relación con Alumno
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    /**
     * Relación con GrupoPlan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(GrupoPlan::class, 'plan_id');
    }
}
