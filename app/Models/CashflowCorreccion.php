<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashflowCorreccion extends Model
{
    protected $table = 'cashflow_correcciones';
    protected $fillable = ['movimiento_id', 'admin_id', 'accion', 'clave', 'huella', 'version', 'antes', 'despues', 'motivo'];
    protected $casts = ['antes' => 'array', 'despues' => 'array', 'version' => 'integer'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('El historial de correcciones se conserva intacto.'));
        static::deleting(fn () => throw new \LogicException('El historial de correcciones no se elimina.'));
    }

    public function admin(): BelongsTo { return $this->belongsTo(User::class, 'admin_id'); }
}
