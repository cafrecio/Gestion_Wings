<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashflowMovimiento extends Model
{
    protected $table = 'cashflow_movimientos';

    /**
     * Valores válidos de referencia_tipo (D2). Antes eran strings sueltos
     * y el seeder usaba 'MOVIMIENTO_OPERATIVO' para lo mismo que el
     * servicio marcaba 'CAJA_OPERATIVA', lo que causó movimientos
     * duplicados en el historial. Usar SIEMPRE estas constantes.
     */
    const REF_CAJA        = 'CAJA_OPERATIVA'; // reflejo de una caja validada
    const REF_PAGO_CUOTA  = 'PAGO_CUOTA';     // cobro de cuota directo por admin
    const REF_LIQUIDACION = 'LIQUIDACION';    // pago de liquidación a profesor
    const REF_CORRECCION = 'CORRECCION_DIRECTO'; // contraasiento de un directo, no editable

    protected $fillable = [
        'reporte_deporte_id',
        'reporte_clasificacion',
        'reporte_tipo',
        'fecha',
        'subrubro_id',
        'tipo_caja_id',
        'monto',
        'observaciones',
        'usuario_admin_id',
        'referencia_tipo',
        'referencia_id',
        'movimiento_operativo_id',
        'contra_origen_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'anulado_at' => 'datetime',
        'version_correccion' => 'integer',
    ];

    /**
     * Relación con Subrubro
     */
    public function subrubro(): BelongsTo
    {
        return $this->belongsTo(Subrubro::class);
    }

    /**
     * Relación con Usuario Admin
     */
    public function usuarioAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_admin_id');
    }

    /**
     * Relación con TipoCaja
     */
    public function tipoCaja(): BelongsTo
    {
        return $this->belongsTo(TipoCaja::class);
    }

    /**
     * Relación con MovimientoOperativo (origen cuando viene de caja validada)
     */
    public function movimientoOperativo(): BelongsTo
    {
        return $this->belongsTo(MovimientoOperativo::class, 'movimiento_operativo_id');
    }

    public function esDirecto(): bool
    {
        return $this->referencia_tipo === null && $this->referencia_id === null
            && $this->movimiento_operativo_id === null && $this->contra_origen_id === null;
    }

    public function puedeCorregirse(): bool
    {
        return $this->esDirecto() && $this->anulado_at === null && !$this->subrubro?->es_reservado_sistema;
    }

    public function correcciones(): HasMany { return $this->hasMany(CashflowCorreccion::class, 'movimiento_id'); }
    public function contraOrigen(): BelongsTo { return $this->belongsTo(self::class, 'contra_origen_id'); }
}
