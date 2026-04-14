<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profesor extends Model
{
    protected $table = 'profesores';

    protected $fillable = [
        'deporte_id',
        'nombre',
        'apellido',
        'dni',
        'fecha_nacimiento',
        'direccion',
        'localidad',
        'email',
        'telefono',
        'valor_hora',
        'porcentaje_comision',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_nacimiento' => 'date',
        'valor_hora' => 'decimal:2',
        'porcentaje_comision' => 'decimal:2',
    ];

    /**
     * Relación con Deporte
     */
    public function deporte(): BelongsTo
    {
        return $this->belongsTo(Deporte::class);
    }

    /**
     * Relación con Clases (muchos a muchos)
     * Un profesor puede dar muchas clases
     */
    public function clases(): BelongsToMany
    {
        return $this->belongsToMany(Clase::class, 'clase_profesor')
            ->withTimestamps();
    }

    /**
     * Relación con Liquidaciones
     */
    public function liquidaciones(): HasMany
    {
        return $this->hasMany(Liquidacion::class);
    }

    /**
     * Obtener nombre completo del profesor
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    /**
     * Obtener el tipo de liquidación heredado del deporte
     */
    public function getTipoLiquidacionAttribute(): ?string
    {
        return $this->deporte?->tipo_liquidacion;
    }

    /**
     * Verificar si el profesor liquida por hora
     */
    public function liquidaPorHora(): bool
    {
        return $this->deporte?->liquidaPorHora() ?? false;
    }

    /**
     * Verificar si el profesor liquida por comisión
     */
    public function liquidaPorComision(): bool
    {
        return $this->deporte?->liquidaPorComision() ?? false;
    }
}
