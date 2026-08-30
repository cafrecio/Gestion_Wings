<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    protected $fillable = [
        'deporte_id',
        'nivel_id',
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
     * Relación con Nivel
     */
    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class);
    }

    /**
     * Nombre compuesto calculado: "{Deporte} — {Nivel}"
     */
    public function getNombreCompletoAttribute(): string
    {
        $this->loadMissing(['deporte', 'nivel']);

        $partes = array_filter([
            $this->deporte?->nombre,
            $this->nivel?->nombre,
        ], fn ($nombre) => filled($nombre));

        return $partes ? implode(' — ', $partes) : 'Sin grupo';
    }

    /**
     * Alias para backward compatibility (servicios usan $grupo->nombre)
     */
    public function getNombreAttribute(): string
    {
        return $this->getNombreCompletoAttribute();
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
