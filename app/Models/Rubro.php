<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubro extends Model
{
    protected $fillable = [
        'nombre',
        'tipo',
        'observacion',
    ];

    /**
     * Relación con Subrubros
     */
    public function subrubros(): HasMany
    {
        return $this->hasMany(Subrubro::class);
    }
}
