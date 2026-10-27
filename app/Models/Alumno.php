<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

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
        });

        // T52 (DU-09): los cambios son siempre para adelante. grupo_desde queda vacío mientras
        // el alumno nunca cambió de grupo: entonces vale su fecha de ingreso, aunque se corrija
        // o sea futura. Solo un cambio de grupo la completa, con el día del cambio.
        static::updating(function ($alumno) {
            if ($alumno->isDirty('grupo_id')) {
                $alumno->grupo_desde = Carbon::now()->toDateString();
            }
        });
    }

    /**
     * T52: alumnos que estaban en el grupo el día de la clase (no los que entraron después).
     */
    public function scopeEnGrupoElDia($query, int $grupoId, Carbon|string $fecha)
    {
        $dia = Carbon::parse($fecha)->toDateString();

        // Siempre hace falta haber ingresado; y si cambió de grupo, haber llegado a este. Las dos
        // cosas a la vez (H5 de Codex: un cambio de grupo antes de un ingreso futuro no adelanta el ingreso).
        return $query->where('grupo_id', $grupoId)
            ->whereDate('fecha_alta', '<=', $dia)
            ->where(fn ($q) => $q->whereNull('grupo_desde')->orWhereDate('grupo_desde', '<=', $dia));
    }

    /**
     * Teléfono de contacto para avisos y cobranza: el celular del alumno, o el del tutor
     * si es menor sin celular propio. Null si no hay ninguno.
     */
    protected function telefonoContacto(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!blank($this->celular)) {
                    return $this->celular;
                }
                if (!blank($this->telefono_tutor)) {
                    return $this->telefono_tutor;
                }
                return null;
            }
        );
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

    /**
     * T65 (DU-48, ALU-024): Un alumno es eliminable por el dueño solo si no tiene cobros,
     * asistencias ni movimientos asociados.
     */
    public function esEliminable(): bool
    {
        return $this->motivoNoEliminable() === null;
    }

    public function motivoNoEliminable(): ?string
    {
        if ($this->pagos()->exists()) {
            return 'Tiene pagos registrados.';
        }

        if ($this->asistencias()->exists() || $this->asistenciaExcesos()->exists()) {
            return 'Tiene asistencias registradas.';
        }

        if (MovimientoOperativo::where('alumno_id', $this->id)->exists()) {
            return 'Tiene movimientos de caja asociados.';
        }

        $tieneCuotasConCobro = $this->deudaCuotas()
            ->where(function ($q) {
                $q->where('monto_pagado', '>', 0)
                    ->orWhere('estado', '!=', DeudaCuota::ESTADO_PENDIENTE);
            })->exists();

        if ($tieneCuotasConCobro) {
            return 'Tiene cuotas con cobro o condonadas.';
        }

        $tieneCargosConCobro = CargoAlumno::where('alumno_id', $this->id)
            ->where(function ($q) {
                $q->where('monto_condonado', '>', 0)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pago_cargo_alumno')
                            ->whereColumn('pago_cargo_alumno.cargo_alumno_id', 'cargos_alumno.id');
                    });
            })->exists();

        if ($tieneCargosConCobro) {
            return 'Tiene cargos con cobro o condonados.';
        }

        return null;
    }

    /**
     * T54 (DU-13, ALU-020): Búsqueda de alumnos por nombre, apellido o DNI.
     * Permite múltiples palabras en cualquier orden (cada palabra debe coincidir con
     * nombre, apellido o DNI). Comparación insensible a mayúsculas y acentos
     * (garantizada por la colación utf8mb4_unicode_ci en MySQL/MariaDB).
     */
    public function scopeBuscar($query, ?string $termino)
    {
        $termino = trim((string) $termino);
        if ($termino === '') {
            return $query;
        }

        $palabras = preg_split('/\s+/', $termino, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($palabras)) {
            return $query;
        }

        return $query->where(function ($q) use ($palabras) {
            foreach ($palabras as $palabra) {
                $escapada = addcslashes($palabra, '%_\\');
                $q->where(function ($sub) use ($escapada) {
                    $sub->where('nombre', 'like', "%{$escapada}%")
                        ->orWhere('apellido', 'like', "%{$escapada}%")
                        ->orWhere('dni', 'like', "%{$escapada}%");
                });
            }
        });
    }
}
