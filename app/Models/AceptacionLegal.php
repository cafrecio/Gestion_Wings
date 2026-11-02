<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AceptacionLegal extends Model
{
    protected $table = 'aceptaciones_legales';

    protected $guarded = ['id'];

    protected $casts = ['aceptado_en' => 'datetime'];
}
