<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumno extends Model
{
    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'fecha_nacimiento',
        'celular',
        'nombre_tutor',
        'telefono_tutor',
        'email',
        'deporte_id',
        'grupo_id',
        'fecha_alta',
        'activo',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_alta' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Boot del modelo para setear valores automáticos
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($alumno) {
            // Setear fecha_alta automáticamente si no se proveyó
            if (empty($alumno->fecha_alta)) {
                $alumno->fecha_alta = Carbon::now()->toDateString();
            }

            // Si el alumno es mayor de edad, forzar campos de tutor a null (defensivo)
            if (!$alumno->esMenorDeEdad()) {
                $alumno->nombre_tutor = null;
                $alumno->telefono_tutor = null;
            }
        });

        static::updating(function ($alumno) {
            // Si el alumno es mayor de edad, forzar campos de tutor a null (defensivo)
            if (!$alumno->esMenorDeEdad()) {
                $alumno->nombre_tutor = null;
                $alumno->telefono_tutor = null;
            }
        });
    }

    /**
     * Accessor para es_menor (calculado dinámicamente)
     * 
     * No existe columna en BD, se calcula en base a fecha_nacimiento
     */
    protected function esMenor(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->esMenorDeEdad(),
        );
    }

    /**
     * Método privado para calcular si es menor de edad
     */
    private function esMenorDeEdad(): bool
    {
        if (!$this->fecha_nacimiento) {
            return false;
        }

        return $this->fecha_nacimiento->diffInYears(Carbon::now()) < 18;
    }

    /**
     * Relación con Deporte
     */
    public function deporte(): BelongsTo
    {
        return $this->belongsTo(Deporte::class);
    }

    /**
     * Relación con Grupo
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * Relación con Pagos
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /**
     * Relación con AlumnoPlanes
     */
    public function planes(): HasMany
    {
        return $this->hasMany(AlumnoPlan::class);
    }

    /**
     * Obtener el plan activo actual del alumno
     */
    public function planActivo()
    {
        return $this->hasOne(AlumnoPlan::class)->where('activo', true)->latest();
    }

    /**
     * Verificar si el alumno tiene pagos registrados
     */
    public function tienePagos(): bool
    {
        return $this->pagos()->exists();
    }

    /**
     * Relación con Asistencias
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    /**
     * Relación con DeudaCuotas
     */
    public function deudaCuotas(): HasMany
    {
        return $this->hasMany(DeudaCuota::class);
    }
}
