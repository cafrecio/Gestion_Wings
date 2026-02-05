<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clase extends Model
{
    protected $table = 'clases';

    protected $fillable = [
        'grupo_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'validada_para_liquidacion',
        'cancelada',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
        'validada_para_liquidacion' => 'boolean',
        'cancelada' => 'boolean',
    ];

    /**
     * Boot del modelo para setear valores automáticos
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($clase) {
            // Si no se provee hora_fin, calcular como hora_inicio + 1 hora
            if (empty($clase->hora_fin) && !empty($clase->hora_inicio)) {
                $horaInicio = Carbon::parse($clase->hora_inicio);
                $clase->hora_fin = $horaInicio->addHour()->format('H:i:s');
            }
        });
    }

    /**
     * Relación con Grupo
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * Relación con Profesores (muchos a muchos)
     * Una clase puede tener muchos profesores
     */
    public function profesores(): BelongsToMany
    {
        return $this->belongsToMany(Profesor::class, 'clase_profesor')
            ->withTimestamps();
    }

    /**
     * Relación con Asistencias
     * Una clase tiene muchas asistencias
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    /**
     * Verificar si esta clase se solapa con otra en fecha y horario
     *
     * @param string $fecha
     * @param string $horaInicio
     * @param string $horaFin
     * @return bool
     */
    public function seSolapaCon(string $fecha, string $horaInicio, string $horaFin): bool
    {
        if ($this->fecha->format('Y-m-d') !== $fecha) {
            return false;
        }

        $estaInicio = Carbon::parse($this->hora_inicio);
        $estaFin = Carbon::parse($this->hora_fin);
        $otraInicio = Carbon::parse($horaInicio);
        $otraFin = Carbon::parse($horaFin);

        // Solapamiento: inicio1 < fin2 AND inicio2 < fin1
        return $estaInicio < $otraFin && $otraInicio < $estaFin;
    }

    /**
     * Verificar si la clase tiene asistencias registradas
     */
    public function tieneAsistencias(): bool
    {
        return $this->asistencias()->where('presente', true)->exists();
    }

    /**
     * Verificar si la clase es liquidable
     * Liquidable si: tiene asistencias O está validada manualmente Y NO está cancelada
     */
    public function esLiquidable(): bool
    {
        if ($this->cancelada) {
            return false;
        }

        return $this->tieneAsistencias() || $this->validada_para_liquidacion;
    }
}
