<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

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

        static::created(function ($plan) {
            $plan->registrarPrecio(null);
        });

        static::updated(function ($plan) {
            if ($plan->wasChanged('precio_mensual')) {
                $plan->registrarPrecio(today()->toDateString());
            }
        });
    }

    /** Precio e historial se guardan juntos; los cambios del mismo plan se serializan. */
    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            if ($this->exists) {
                static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            }
            return parent::save($options);
        });
    }

    private function registrarPrecio(?string $desde): void
    {
        DB::table('grupo_plan_precios')->insert([
            'grupo_plan_id' => $this->id,
            'precio_mensual' => $this->precio_mensual,
            'vigente_desde' => $desde,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * T44: precio del período para esta inscripción deportiva. La fecha de alta original
     * no cambia al reingresar ni al cambiar de plan. En su mes de alta toma el precio de
     * ese día; en los demás meses toma el último cambio anterior al comienzo del mes.
     * NULL representa un alumno que ya estaba. El catálogo pasa hoy como fecha de alta.
     */
    public function precioParaPeriodo(string $periodo, CarbonInterface|string|null $fechaAlta): float
    {
        $inicio = Carbon::createFromFormat('!Y-m', $periodo);
        $alta = $fechaAlta === null ? null : Carbon::parse($fechaAlta);
        $corte = $alta && $alta->format('Y-m') === $periodo
            ? $alta->toDateString()
            : $inicio->subDay()->toDateString();

        $precio = DB::table('grupo_plan_precios')->where('grupo_plan_id', $this->id)
            ->where(fn ($q) => $q->whereNull('vigente_desde')->orWhere('vigente_desde', '<=', $corte))
            ->orderByDesc('vigente_desde')->orderByDesc('id')->value('precio_mensual');
        if ($precio === null) {
            throw new \LogicException('El plan no tiene historial de precios.');
        }
        return (float) $precio;
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
