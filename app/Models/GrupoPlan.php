<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrupoPlan extends Model
{
    protected $table = 'grupo_planes';

    protected $fillable = [
        'grupo_id',
        'clases_por_semana',
        'precio_mensual',
        'activo',
    ];

    protected $casts = [
        'clases_por_semana' => 'integer',
        'precio_mensual' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * Boot del modelo para validaciones automáticas
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            $plan->validarReglas();
        });

        static::updating(function ($plan) {
            $plan->validarReglas();
        });
    }

    /**
     * Validar reglas de negocio
     */
    private function validarReglas(): void
    {
        if ($this->clases_por_semana <= 0) {
            throw new \InvalidArgumentException('Las clases por semana deben ser mayor a 0.');
        }

        if ($this->precio_mensual < 0) {
            throw new \InvalidArgumentException('El precio mensual no puede ser negativo.');
        }
    }

    /**
     * Relación con Grupo
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }
}
