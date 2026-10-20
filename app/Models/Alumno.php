<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Alumno extends Model
{
    protected $attributes = [
        'activo' => true,
    ];

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
        'grupo_desde' => 'date',
        'activo' => 'boolean',
        'alta_cuota' => 'array',
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
            // T52: está en su grupo desde que ingresó.
            if (empty($alumno->grupo_desde)) {
                $alumno->grupo_desde = $alumno->fecha_alta;
            }
        });

        // T52 (DU-09): los cambios son siempre para adelante. Un cambio de grupo cuenta
        // desde hoy; si se corrige la fecha de ingreso de quien nunca cambió de grupo, su
        // fecha en el grupo la acompaña.
        static::updating(function ($alumno) {
            if ($alumno->isDirty('grupo_id')) {
                $alumno->grupo_desde = Carbon::now()->toDateString();
            } elseif ($alumno->isDirty('fecha_alta')) {
                $anterior = $alumno->getOriginal('fecha_alta');
                $desde = $alumno->getOriginal('grupo_desde');
                if ($desde === null || ($anterior && Carbon::parse($desde)->isSameDay($anterior))) {
                    $alumno->grupo_desde = $alumno->fecha_alta;
                }
            }
        });
    }

    /**
     * T52: alumnos que estaban en el grupo el día de la clase (no los que entraron después).
     */
    public function scopeEnGrupoElDia($query, int $grupoId, Carbon|string $fecha)
    {
        $dia = Carbon::parse($fecha)->toDateString();

        return $query->where('grupo_id', $grupoId)
            ->where(fn ($q) => $q->whereNull('grupo_desde')->orWhereDate('grupo_desde', '<=', $dia));
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
        return $this->hasOne(AlumnoPlan::class)->where(function ($q) {
            $q->where(fn ($vigente) => $vigente->where('fecha_desde', '<=', today()->endOfDay())
                ->where(fn ($fin) => $fin->whereNull('fecha_hasta')->orWhere('fecha_hasta', '>=', today())))
                // Conservar la asignación inicial de un ingreso que todavía no comenzó.
                ->orWhere(fn ($futuro) => $futuro->where('activo', true)
                    ->whereHas('alumno', fn ($a) => $a->where('fecha_alta', '>', today())));
        })->orderByDesc('fecha_desde')->orderByDesc('id');
    }

    public function planProgramado(): HasOne
    {
        return $this->hasOne(AlumnoPlan::class)->where('activo', true)
            ->where('fecha_desde', '>', today()->endOfDay())->orderByDesc('fecha_desde')->orderByDesc('id');
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

    public function revisionesCobranza(): HasMany
    {
        return $this->hasMany(AlumnoRevisionCobranza::class);
    }

    public function asistenciaExcesos(): HasMany
    {
        return $this->hasMany(AsistenciaExceso::class);
    }
}
