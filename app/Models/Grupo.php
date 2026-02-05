<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    protected $fillable = [
        'nombre',
        'deporte_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con Deporte
     */
    public function deporte(): BelongsTo
    {
        return $this->belongsTo(Deporte::class);
    }

    /**
     * Relación con Alumno
     */
    public function alumnos(): HasMany
    {
        return $this->hasMany(Alumno::class);
    }

    /**
     * Relación con GrupoPlan (planes de precios)
     */
    public function planes(): HasMany
    {
        return $this->hasMany(GrupoPlan::class);
    }

    /**
     * Obtener solo planes activos
     */
    public function planesActivos(): HasMany
    {
        return $this->hasMany(GrupoPlan::class)->where('activo', true);
    }

    /**
     * Relación con Clases
     */
    public function clases(): HasMany
    {
        return $this->hasMany(Clase::class);
    }
}
