<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrimeraCarga extends Model
{
    protected $table = 'primera_carga';
    protected $guarded = [];
    protected $casts = ['detalle' => 'array'];

    public static function pendiente(): bool
    {
        return static::whereKey(1)->value('estado') === 'PENDIENTE';
    }
}
