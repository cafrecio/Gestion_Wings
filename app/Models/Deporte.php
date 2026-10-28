<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deporte extends Model
{
    use \App\Models\Concerns\GuardaHistorialAtomico;

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

    public function vigenciasLiquidacion(): HasMany
    {
        return $this->hasMany(DeporteLiquidacionVigencia::class)->orderByDesc('vigente_desde')->orderByDesc('id');
    }

    public function tipoProgramadoParaPeriodo(int $mes, int $anio): ?string
    {
        $fecha = sprintf('%04d-%02d-01', $anio, $mes);
        return $this->vigenciasLiquidacion->first(fn ($v) => $v->vigente_desde->toDateString() <= $fecha)?->tipo;
    }

    public function tipoLiquidacionParaPeriodo(int $mes, int $anio): ?string
    {
        return $this->tipoProgramadoParaPeriodo($mes, $anio) ?? ($this->attributes['tipo_liquidacion'] ?? null);
    }

    public function getTipoLiquidacionAttribute($valor): ?string
    {
        return $this->exists ? $this->tipoLiquidacionParaPeriodo(today()->month, today()->year) : $valor;
    }

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
