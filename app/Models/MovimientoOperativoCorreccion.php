<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoOperativoCorreccion extends Model
{
    protected $table = 'movimiento_operativo_correcciones';
    public const UPDATED_AT = null;
    protected $fillable = ['caja_operativa_id', 'movimiento_id', 'usuario_id', 'autor', 'accion', 'antes', 'despues'];
    protected $casts = ['antes' => 'array', 'despues' => 'array'];
}
