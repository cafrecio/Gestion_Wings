<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeporteLiquidacionVigencia extends Model
{
    protected $table = 'deporte_liquidacion_vigencias';
    protected $fillable = ['deporte_id', 'tipo', 'vigente_desde', 'usuario_id'];
    protected $casts = ['vigente_desde' => 'date'];
}
