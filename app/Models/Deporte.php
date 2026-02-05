<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deporte extends Model
{
    const TIPO_LIQUIDACION_HORA = 'HORA';
    const TIPO_LIQUIDACION_COMISION = 'COMISION';

    protected $fillable = [
        'nombre',
        'tipo_liquidacion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con Grupo
     */
    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }

    /**
     * Relación con Alumno
     */
    public function alumnos(): HasMany
    {
        return $this->hasMany(Alumno::class);
    }

    /**
     * Relación con Profesor
     */
    public function profesores(): HasMany
    {
        return $this->hasMany(Profesor::class);
    }

    /**
     * Verificar si el deporte liquida por hora
     */
    public function liquidaPorHora(): bool
    {
        return $this->tipo_liquidacion === self::TIPO_LIQUIDACION_HORA;
    }

    /**
     * Verificar si el deporte liquida por comisión
     */
    public function liquidaPorComision(): bool
    {
        return $this->tipo_liquidacion === self::TIPO_LIQUIDACION_COMISION;
    }
}
